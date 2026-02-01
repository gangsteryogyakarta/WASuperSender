<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Jobs\SendCampaignMessageJob;
use Illuminate\Support\Str;

class CampaignService
{
    public function __construct(
        private WahaService $wahaService,
        private SegmentService $segmentService
    ) {}

    /**
     * Create and schedule a campaign
     */
    public function createCampaign(array $data): Campaign
    {
        $campaign = Campaign::create([
            'name' => $data['name'],
            'message_template' => $data['message_template'],
            'media_path' => $data['media_path'] ?? null,
            'status' => $data['scheduled_at'] ? 'scheduled' : 'draft',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'segment_id' => $data['segment_id'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        if ($data['segment_id']) {
            $this->calculateRecipients($campaign);
        }

        return $campaign;
    }

    /**
     * Calculate total recipients for campaign
     */
    public function calculateRecipients(Campaign $campaign): void
    {
        if ($campaign->segment) {
            $count = $campaign->segment->contacts()->count();
            $campaign->update(['total_recipients' => $count]);
        }
    }

    /**
     * Start campaign execution
     */
    public function startCampaign(Campaign $campaign, string $session): void
    {
        $campaign->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $contacts = $campaign->segment 
            ? $campaign->segment->contacts 
            : Contact::all();

        $delay = 0;
        $delayIncrement = config('waha.rate_limits.delay_between_messages');

        foreach ($contacts as $contact) {
            SendCampaignMessageJob::dispatch($campaign, $contact, $session)
                ->delay(now()->addSeconds($delay));
            
            $delay += $delayIncrement;
        }
    }

    /**
     * Pause a running campaign
     */
    public function pauseCampaign(Campaign $campaign): void
    {
        $campaign->update(['status' => 'paused']);
    }

    /**
     * Resume a paused campaign
     */
    public function resumeCampaign(Campaign $campaign, string $session): void
    {
        $pendingMessages = $campaign->messages()
            ->where('status', 'pending')
            ->with('contact')
            ->get();

        $campaign->update(['status' => 'running']);

        $delay = 0;
        $delayIncrement = config('waha.rate_limits.delay_between_messages');

        foreach ($pendingMessages as $message) {
            SendCampaignMessageJob::dispatch($campaign, $message->contact, $session)
                ->delay(now()->addSeconds($delay));
            
            $delay += $delayIncrement;
        }
    }

    /**
     * Parse message template with contact variables
     */
    public function parseTemplate(string $template, Contact $contact): string
    {
        $variables = [
            '[Nama]' => $contact->name,
            '[nama]' => $contact->name,
            '[Phone]' => $contact->phone,
            '[Email]' => $contact->email ?? '',
            '[Kendaraan]' => $contact->vehicle_interest ?? '',
            '[Budget]' => $contact->budget ? number_format($contact->budget) : '',
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    /**
     * Get campaign statistics
     */
    public function getStatistics(Campaign $campaign): array
    {
        return [
            'total' => $campaign->total_recipients,
            'sent' => $campaign->sent_count,
            'delivered' => $campaign->delivered_count,
            'read' => $campaign->read_count,
            'failed' => $campaign->failed_count,
            'pending' => $campaign->total_recipients - $campaign->sent_count - $campaign->failed_count,
            'delivery_rate' => $campaign->sent_count > 0 
                ? round(($campaign->delivered_count / $campaign->sent_count) * 100, 2) 
                : 0,
            'read_rate' => $campaign->delivered_count > 0 
                ? round(($campaign->read_count / $campaign->delivered_count) * 100, 2) 
                : 0,
        ];
    }
}
