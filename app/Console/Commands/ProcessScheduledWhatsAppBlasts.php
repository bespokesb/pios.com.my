<?php

namespace App\Console\Commands;

use App\Models\WhatsAppCampaign;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Console\Command;

class ProcessScheduledWhatsAppBlasts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-scheduled-blasts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and auto-send due scheduled WhatsApp promotion blasts';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppManager $manager): int
    {
        $dueCampaigns = WhatsAppCampaign::query()
            ->where('status', WhatsAppCampaign::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->get();

        if ($dueCampaigns->isEmpty()) {
            $this->info('No scheduled WhatsApp blasts due.');

            return self::SUCCESS;
        }

        foreach ($dueCampaigns as $campaign) {
            $this->info("Processing Campaign #{$campaign->id}: {$campaign->title}");
            $result = $manager->executeCampaign($campaign, true);
            $this->info("Done: Sent {$result['sent']}/{$result['total']}, Failed {$result['failed']}");
        }

        return self::SUCCESS;
    }
}
