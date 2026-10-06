<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seeds the real HICC zonal structure from the "HICC ZONAL PASTORS" sheet.
 *
 * It removes the demo zones and replaces them with the 8 official zones,
 * creating a member record for each responsible pastor and assigning them
 * as the zone leader.
 */
class HiccZonalPastorsSeeder extends Seeder
{
    /**
     * Official zones: [zone name, responsible pastor, contact].
     *
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    protected array $zones = [
        ['Bethel Zone',     'Evord Sadock',     '255769029129'],
        ['Mount Zion Zone', 'Rebecca Zabron',   '255745409249'],
        ['Eagle Zone',      'Isack Waitara',    '255686490509'],
        ['Jerusalem Zone',  'Mary Ishengoma',   '255744333232'],
        ['Penuel Zone',     'Dorcas Rehani',    '255746804030'],
        ['Kakebe Zone',     'Thomas Hamis',     '255795016319'],
        ['Kanindo Zone',    'Evord Sadock',     '255769029129'],
        ['Kanyerere Zone',  'Shadrack Kalenga', '255744279380'],
    ];

    public function run(): void
    {
        $this->command->info('🗺️  Inaweka Zone halisi za HICC...');

        // 1. Clear existing (demo) zones and their dependent records.
        $this->clearExistingZones();

        // 2. Create the zones with their responsible pastors.
        foreach ($this->zones as [$zoneName, $pastorName, $contact]) {
            $leader = $this->findOrCreatePastor($pastorName, $contact);

            DB::table('small_groups')->insert([
                'name'         => $zoneName,
                'description'  => 'Zone ya HICC inayoongozwa na ' . $pastorName,
                'leader_id'    => $leader->id,
                'meeting_day'  => null,
                'meeting_time' => null,
                'location'     => null,
                'max_members'  => 20,
                'status'       => 'active',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $this->command->info("  ✅ {$zoneName} — {$pastorName} ({$contact})");
        }

        $this->command->info('  🗺️  Jumla ya Zone: ' . DB::table('small_groups')->count());
    }

    /**
     * Remove all existing zones plus any records that reference them.
     */
    protected function clearExistingZones(): void
    {
        $dependentTables = [
            'small_group_member',
            'small_group_meetings',
            'small_group_offerings',
            'small_group_responses',
        ];

        foreach ($dependentTables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        DB::table('small_groups')->delete();
    }

    /**
     * Find an existing member by name or create a new pastor record.
     */
    protected function findOrCreatePastor(string $name, string $contact): Member
    {
        $existing = Member::where('full_name', $name)->first();
        if ($existing) {
            if (empty($existing->phone)) {
                $existing->update(['phone' => $contact]);
            }
            return $existing;
        }

        return Member::create([
            'full_name'         => $name,
            'phone'             => $contact,
            'email'             => $this->generateUniqueEmail($name),
            'status'            => 'active',
            'registration_type' => 'zonal_pastor',
        ]);
    }

    /**
     * Build a unique placeholder email for a pastor.
     */
    protected function generateUniqueEmail(string $name): string
    {
        $base = Str::slug($name, '.');
        if (empty($base)) {
            $base = 'mchungaji';
        }

        $candidate = $base . '@hosannachurch.org';
        $i = 1;
        while (Member::where('email', $candidate)->exists()) {
            $candidate = $base . $i . '@hosannachurch.org';
            $i++;
        }

        return $candidate;
    }
}
