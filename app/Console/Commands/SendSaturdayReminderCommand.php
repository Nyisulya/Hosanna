<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\SystemSetting;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSaturdayReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:saturday-reminder 
                            {--force : Run the command regardless of current day}
                            {--preview : Show recipients and message without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tuma SMS za kuwakumbusha na kuwakaribisha waumini kwenye ibada ya Jumapili (hutumwa Jumamosi)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('====================================================');
        $this->info('  KUTUMA SMS ZA MWALIKO WA IBADA YA JUMAPILI');
        $this->info('====================================================');

        // Check if enabled in settings
        $enabled = SystemSetting::where('key', 'saturday_reminder_enabled')->value('value');
        if ($enabled === '0' || $enabled === 'false') {
            $this->warn('Kipengele hiki kimezimwa kwenye mipangilio (System Settings).');
            return Command::SUCCESS;
        }

        // Verify if today is Saturday unless --force is passed
        $today = Carbon::now();
        if ($today->dayOfWeek !== Carbon::SATURDAY && !$this->option('force')) {
            $this->warn("Leo sio Jumamosi (Leo ni: " . $today->translatedFormat('l') . ").");
            $this->comment("Ili kutuma leo kwa majaribio, tumia: php artisan sms:saturday-reminder --force");
            return Command::SUCCESS;
        }

        // Fetch Church Name
        $churchName = SmsService::getChurchName();
        $churchUpper = strtoupper($churchName);

        // Fetch or default template
        $template = SystemSetting::where('key', 'saturday_reminder_message')->value('value');
        if (empty(trim((string)$template))) {
            $header = SmsService::getHeader('MWALIKO WA IBADA YA JUMAPILI');
            $template = "{$header}\n" .
                        "Bwana asifiwe Ndugu {name}!\n\n" .
                        "Uongozi wa kanisa unakukaribisha kwa furaha wewe na familia yako katika Ibada ya Jumapili kesho hapa {$churchName}.\n\n" .
                        "Karibu tujumuike pamoja kumwabudu Bwana wetu. Ibada inaanza saa 3:00 asubuhi.\n\n" .
                        "\"Nalifurahi waliponiambia: Na twende nyumbani mwa Bwana.\" - Zaburi 122:1\n" .
                        "Mungu akubariki na kukulinda!";
        } elseif (!str_starts_with(trim($template), '*')) {
            $header = SmsService::getHeader('MWALIKO WA IBADA YA JUMAPILI');
            $template = "{$header}\n{$template}";
        }

        // Fetch active members with phone numbers
        $members = Member::where('status', 'active')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        $totalMembers = $members->count();

        if ($totalMembers === 0) {
            $this->warn('Hakuna waumini wanaopatikana (active members with phone numbers).');
            return Command::SUCCESS;
        }

        $this->info("Idadi ya waumini watakaotumiwa: {$totalMembers}");
        $this->line("Ujumbe wa mfano:");
        $exampleMsg = str_replace(
            ['{name}', '{church_name}'], 
            [$members->first()->full_name, $churchName], 
            $template
        );
        $this->comment("\"{$exampleMsg}\"");
        $this->newLine();

        if ($this->option('preview')) {
            $this->info("Preview Mode: SMS hazikutumwa.");
            return Command::SUCCESS;
        }

        $isPersonalized = str_contains($template, '{name}');
        $sentCount = 0;
        $failedCount = 0;

        if ($isPersonalized) {
            $this->info("Inatuma ujumbe mmoja mmoja kwa kila muumini (Personalized)...");
            $bar = $this->output->createProgressBar($totalMembers);
            $bar->start();

            foreach ($members as $member) {
                $msg = str_replace(
                    ['{name}', '{church_name}'], 
                    [$member->full_name, $churchName], 
                    $template
                );

                $sent = SmsService::send($member->phone, $msg);
                if ($sent) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine(2);

        } else {
            $this->info("Inatuma kwa mfumo wa haraka (Bulk Broadcast)...");
            $phones = $members->pluck('phone')->toArray();
            $msg = str_replace('{church_name}', $churchName, $template);

            $result = SmsService::sendBulk($phones, $msg);
            $sentCount = $result['sent_count'];
            $failedCount = $result['total'] - $result['sent_count'];
        }

        $this->info("Matokeo: Zilizofanikiwa: {$sentCount} | Zilizoshindikana: {$failedCount}");
        Log::info("Saturday Reminder SMS completed. Sent: {$sentCount}, Failed: {$failedCount}");

        return Command::SUCCESS;
    }
}
