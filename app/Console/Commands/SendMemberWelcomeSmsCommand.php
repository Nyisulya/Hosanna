<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SendMemberWelcomeSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'members:send-welcome-sms
                            {--all : Tuma kwa washiriki wote wenye namba za simu}
                            {--member= : Tuma kwa mshiriki mmoja tu (kwa ID au Member Number)}
                            {--phone= : Tuma kwa mshiriki mwenye namba hii ya simu}
                            {--test= : Tuma SMS moja tu ya majaribio kwenye namba hii}
                            {--limit= : Idadi ya washiriki wa kutuma (kwa mfano --limit=5)}
                            {--delay=1 : Sekunde za kusubiri kati ya kila SMS}
                            {--password=password123 : Default password ya kuingilia kwenye mfumo}
                            {--force : Usiulize uthibitisho, tuma mara moja}
                            {--preview : Tazama tu orodha na mfano wa SMS bila kutuma chochote}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tuma SMS ya kumkaribisha mshiriki kwenye mfumo ikiwa na taarifa za kuingia (Credentials & Link)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===========================================================');
        $this->info('  KUTUMA SMS ZA KUINGIA KWENYE MFUMO KWA WASHIRIKI');
        $this->info('===========================================================');

        $preview = $this->option('preview');
        $defaultPassword = $this->option('password') ?: 'password123';
        $delay = max(0, (int) $this->option('delay'));

        // Direct test mode to single phone without altering DB members
        if ($testPhone = $this->option('test')) {
            $this->info("Kutuma SMS ya majaribio kwenda namba: {$testPhone}");
            $sampleMsg = SmsService::buildRegistrationWelcomeMessage('Majaribio', 'mshiriki@hosannachurch.org', $defaultPassword);
            $this->line("\n[UJUMBE UTAKAOTUMWA]:\n");
            $this->line($sampleMsg);
            $this->line("-----------------------------------------------------------\n");

            if ($preview) {
                $this->comment("[PREVIEW] Hakuna SMS iliyotumwa kwani umeweka --preview.");
                return Command::SUCCESS;
            }

            $this->output->write("Inatuma SMS...");
            $sent = SmsService::sendRegistrationWelcome($testPhone, 'Mshiriki wa Majaribio', 'mshiriki@hosannachurch.org', $defaultPassword);
            if ($sent) {
                $this->info(" [IMEFANIKIWA]");
                $this->info("SMS ya majaribio imetumwa kikamilifu kwenda {$testPhone}!");
                return Command::SUCCESS;
            } else {
                $this->error(" [IMEFELI]");
                $this->error("Kutuma kumeshindwa. Tafadhali hakikisha kifaa cha SMS Gate kipo hewani mtandaoni.");
                return Command::FAILURE;
            }
        }

        // Build query
        $query = Member::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        // Filter by specific member
        if ($memberParam = $this->option('member')) {
            $query->where(function ($q) use ($memberParam) {
                $q->where('id', $memberParam)
                  ->orWhere('member_number', $memberParam);
            });
        }

        // Filter by specific phone
        if ($phoneParam = $this->option('phone')) {
            $cleaned = preg_replace('/[^0-9]/', '', $phoneParam);
            $query->where(function ($q) use ($phoneParam, $cleaned) {
                $q->where('phone', 'like', "%{$phoneParam}%")
                  ->orWhere('phone', 'like', "%{$cleaned}%");
            });
        }

        // Check if any filter or --all was passed
        if (!$this->option('all') && !$this->option('member') && !$this->option('phone') && !$preview) {
            $this->warn('Tafadhali chagua vigezo vya kutuma:');
            $this->line('  1. Jaribu kwa namba yako tu:   php artisan members:send-welcome-sms --test=0756670798');
            $this->line('  2. Kutazama tu (Preview):      php artisan members:send-welcome-sms --all --preview');
            $this->line('  3. Kutuma kwa wote (VPS):      php artisan members:send-welcome-sms --all');
            $this->line('  4. Kutuma kwa mmoja:           php artisan members:send-welcome-sms --member=ID');
            $this->line('  5. Kutuma kwa wachache:        php artisan members:send-welcome-sms --all --limit=5');
            return Command::INVALID;
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $members = $query->orderBy('id', 'asc')->get();
        $total = $members->count();

        if ($total === 0) {
            $this->warn('Hakuna mshiriki yeyote aliyepatikana kwa vigezo hivi.');
            return Command::SUCCESS;
        }

        $this->info("Washiriki waliopatikana: {$total}");

        // PREVIEW MODE
        if ($preview) {
            $this->comment("\n--- HALI YA MAJARIBIO (PREVIEW MODE) - HAKUNA SMS ITAKAYOTUMWA ---");

            $sampleMember = $members->first();
            $sampleEmail = $sampleMember->email ?: 'mshiriki@hosannachurch.org';
            $sampleMsg = SmsService::buildRegistrationWelcomeMessage($sampleMember->full_name, $sampleEmail, $defaultPassword);

            $this->line("\n[MFANO WA SMS ITAKAYOTUMWA]:\n");
            $this->line($sampleMsg);
            $this->line("\n-----------------------------------------------------------\n");

            $tableRows = [];
            foreach ($members->take(15) as $m) {
                $tableRows[] = [
                    $m->id,
                    $m->member_number,
                    $m->full_name,
                    $m->phone,
                    $m->email ?: '(Itafunguliwa)',
                    $m->user_id ? 'Ndio' : 'Hapana (Itatengenezwa)',
                ];
            }

            $this->table(['ID', 'Namba', 'Jina Kamili', 'Simu', 'Email', 'Ana Akaunti?'], $tableRows);

            if ($total > 15) {
                $remaining = $total - 15;
                $this->comment("... na wengine {$remaining} zaidi.");
            }

            $this->info("\nIli kuanza kutuma SMS kwa washiriki hawa, endesha amri bila --preview:");
            $this->line("  php artisan members:send-welcome-sms --all");
            return Command::SUCCESS;
        }

        // CONFIRMATION
        if (!$this->option('force')) {
            if (!$this->confirm("Je, una uhakika unataka kutuma SMS ya kuingilia kwenye mfumo kwa washiriki hawa {$total}?", false)) {
                $this->warn('Zoezi limesitishwa.');
                return Command::SUCCESS;
            }
        }

        // SENDING PROCESS
        $this->output->progressStart($total);
        $successCount = 0;
        $failCount = 0;

        foreach ($members as $member) {
            try {
                // 1. Ensure Member has an Email
                if (empty($member->email)) {
                    $baseSlug = Str::slug($member->full_name, '.');
                    if (empty($baseSlug)) {
                        $baseSlug = 'mshiriki';
                    }
                    $candidate = $baseSlug . $member->id . '@hosannachurch.org';
                    while (Member::where('email', $candidate)->where('id', '!=', $member->id)->exists() 
                        || User::where('email', $candidate)->exists()) {
                        $candidate = $baseSlug . rand(1000, 9999) . '@hosannachurch.org';
                    }
                    $member->email = $candidate;
                    $member->saveQuietly();
                }

                // 2. Ensure Member has linked User account
                $user = $member->user;
                if (!$user) {
                    User::$createMemberProfile = false;
                    try {
                        $user = User::firstOrCreate(
                            ['email' => $member->email],
                            [
                                'name'     => $member->full_name,
                                'password' => Hash::make($defaultPassword),
                            ]
                        );
                    } finally {
                        User::$createMemberProfile = true;
                    }

                    if (!$user->hasAnyRole(\Spatie\Permission\Models\Role::all())) {
                        $user->assignRole('member');
                    }

                    $member->user_id = $user->id;
                    $member->saveQuietly();
                } else {
                    // Update password to defaultPassword so credentials match SMS
                    $user->password = Hash::make($defaultPassword);
                    $user->saveQuietly();
                }

                // 3. Send SMS
                $sent = SmsService::sendRegistrationWelcome(
                    $member->phone,
                    $member->full_name,
                    $user->email,
                    $defaultPassword
                );

                if ($sent) {
                    $successCount++;
                } else {
                    $failCount++;
                }

            } catch (\Throwable $e) {
                $failCount++;
                \Illuminate\Support\Facades\Log::error("Failed sending welcome SMS to member #{$member->id}: " . $e->getMessage());
            }

            $this->output->progressAdvance();

            if ($delay > 0 && $total > 1) {
                sleep($delay);
            }
        }

        $this->output->progressFinish();

        $this->info("\n===========================================================");
        $this->info("  MATOKEO YA UTUMAJI:");
        $this->info("===========================================================");
        $this->info("  Zilizofanikiwa (Sent): {$successCount}");
        if ($failCount > 0) {
            $this->error("  Zilizoshindwa (Failed): {$failCount}");
        } else {
            $this->info("  Zilizoshindwa (Failed): 0");
        }
        $this->info("===========================================================\n");

        return Command::SUCCESS;
    }
}
