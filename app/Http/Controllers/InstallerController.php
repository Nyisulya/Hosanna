<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\SystemSetting;

class InstallerController extends Controller
{
    /**
     * Step 1: Server Requirements & Permissions Check
     */
    public function index()
    {
        $requirements = [
            'php' => [
                'name'    => 'PHP Version (>= 8.2.0)',
                'current' => phpversion(),
                'passed'  => version_compare(phpversion(), '8.2.0', '>='),
            ],
            'pdo' => [
                'name'   => 'PDO PHP Extension',
                'passed' => extension_loaded('pdo'),
            ],
            'pdo_mysql' => [
                'name'   => 'PDO MySQL Extension',
                'passed' => extension_loaded('pdo_mysql'),
            ],
            'mbstring' => [
                'name'   => 'Mbstring Extension',
                'passed' => extension_loaded('mbstring'),
            ],
            'fileinfo' => [
                'name'   => 'FileInfo Extension',
                'passed' => extension_loaded('fileinfo'),
            ],
            'openssl' => [
                'name'   => 'OpenSSL Extension',
                'passed' => extension_loaded('openssl'),
            ],
            'tokenizer' => [
                'name'   => 'Tokenizer Extension',
                'passed' => extension_loaded('tokenizer'),
            ],
            'xml' => [
                'name'   => 'XML Extension',
                'passed' => extension_loaded('xml'),
            ],
            'ctype' => [
                'name'   => 'Ctype Extension',
                'passed' => extension_loaded('ctype'),
            ],
            'json' => [
                'name'   => 'JSON Extension',
                'passed' => extension_loaded('json'),
            ],
            'bcmath' => [
                'name'   => 'BCMath Extension',
                'passed' => extension_loaded('bcmath'),
            ],
            'curl' => [
                'name'   => 'cURL Extension',
                'passed' => extension_loaded('curl'),
            ],
        ];

        $permissions = [
            'storage' => [
                'name'   => 'storage/',
                'passed' => is_writable(storage_path()),
            ],
            'storage_framework' => [
                'name'   => 'storage/framework/',
                'passed' => is_writable(storage_path('framework')),
            ],
            'storage_logs' => [
                'name'   => 'storage/logs/',
                'passed' => is_writable(storage_path('logs')),
            ],
            'bootstrap_cache' => [
                'name'   => 'bootstrap/cache/',
                'passed' => is_writable(base_path('bootstrap/cache')),
            ],
            'env_file' => [
                'name'   => '.env (au root directory)',
                'passed' => file_exists(base_path('.env')) ? is_writable(base_path('.env')) : is_writable(base_path()),
            ],
        ];

        $allRequirementsPassed = collect($requirements)->every(fn($r) => $r['passed']);
        $allPermissionsPassed = collect($permissions)->every(fn($p) => $p['passed']);

        return view('installer.welcome', compact('requirements', 'permissions', 'allRequirementsPassed', 'allPermissionsPassed'));
    }

    /**
     * Step 2: Database Setup Form
     */
    public function database()
    {
        return view('installer.database');
    }

    /**
     * Step 2: Save Database Configuration & Test Connection
     */
    public function saveDatabase(Request $request)
    {
        $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $host     = $request->input('db_host');
        $port     = $request->input('db_port');
        $database = $request->input('db_database');
        $username = $request->input('db_username');
        $password = $request->input('db_password', '');

        // Test MySQL Connection
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT            => 5,
            ]);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Imeshindwa kuunganisha kwenye Database: ' . $e->getMessage());
        }

        // Write to .env
        $this->updateEnvFile([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => $host,
            'DB_PORT'       => $port,
            'DB_DATABASE'   => $database,
            'DB_USERNAME'   => $username,
            'DB_PASSWORD'   => $password,
        ]);

        return redirect()->route('install.admin')->with('success', 'Database imeunganishwa kwa mafanikio! Endelea na maelezo ya kanisa na msimamizi.');
    }

    /**
     * Step 3: Admin & Church Details Form
     */
    public function admin()
    {
        return view('installer.admin');
    }

    /**
     * Step 4: Execute Installation (Run Migrations, Create Roles, Create Super Admin)
     */
    public function install(Request $request)
    {
        $request->validate([
            'church_name'     => 'required|string|max:255',
            'admin_name'      => 'required|string|max:255',
            'admin_email'     => 'required|email|max:255',
            'admin_password'  => 'required|string|min:6',
            'admin_phone'     => 'nullable|string|max:20',
        ]);

        try {
            // 1. Clear caches
            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            // 2. Generate key if missing
            if (empty(env('APP_KEY'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // 3. Run Migrations
            Artisan::call('migrate', ['--force' => true]);

            // 4. Seed basic roles & permissions
            Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);

            // 5. Create or Update Super Admin
            $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
            
            $user = User::updateOrCreate(
                ['email' => $request->input('admin_email')],
                [
                    'name'              => $request->input('admin_name'),
                    'password'          => Hash::make($request->input('admin_password')),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole('super_admin')) {
                $user->assignRole($adminRole);
            }

            // 6. Save Church Name to settings
            SystemSetting::updateOrCreate(
                ['key' => 'church_name'],
                ['value' => $request->input('church_name')]
            );
            SystemSetting::updateOrCreate(
                ['key' => 'currency'],
                ['value' => 'TZS']
            );

            // 7. Update .env with Church Name & Production mode
            $this->updateEnvFile([
                'APP_NAME'      => '"' . addslashes($request->input('church_name')) . '"',
                'APP_ENV'       => 'production',
                'APP_DEBUG'     => 'false',
                'APP_INSTALLED' => 'true',
            ]);

            // 8. Create storage/installed lock file
            file_put_contents(storage_path('installed'), json_encode([
                'installed_at' => now()->toIso8601String(),
                'version'      => '1.0.0',
                'church_name'  => $request->input('church_name'),
                'admin_email'  => $request->input('admin_email'),
            ], JSON_PRETTY_PRINT));

            // 9. Storage link & optimize
            try {
                Artisan::call('storage:link');
            } catch (\Exception $e) {}

            Artisan::call('optimize:clear');

            return redirect()->route('install.complete')->with([
                'admin_email'    => $request->input('admin_email'),
                'admin_password' => $request->input('admin_password'),
                'church_name'    => $request->input('church_name'),
            ]);

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Hitilafu imetokea wakati wa usakinishaji: ' . $e->getMessage());
        }
    }

    /**
     * Step 5: Installation Complete
     */
    public function complete()
    {
        return view('installer.finish');
    }

    /**
     * Helper: Update values in .env file
     */
    protected function updateEnvFile(array $data)
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $envPath);
            } else {
                touch($envPath);
            }
        }

        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $key = strtoupper($key);
            $pattern = "/^{$key}=(.*)$/m";
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
