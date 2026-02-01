<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Services\CampaignService;
use App\Services\WahaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [5, 30, 120];

    public function __construct(
        public Campaign $campaign,
        public Contact $contact,
        public string $session
    ) {}

    public function handle(WahaService $wahaService, CampaignService $campaignService): void
    {
        // Skip if campaign is paused or completed
        if (in_array($this->campaign->status, ['paused', 'completed', 'failed'])) {
            return;
        }

        // Parse message template
        $messageText = $campaignService->parseTemplate(
            $this->campaign->message_template,
            $this->contact
        );

        // Create message record
        $message = Message::create([
            'contact_id' => $this->contact->id,
            'campaign_id' => $this->campaign->id,
            'direction' => 'outbound',
            'content' => $messageText,
            'status' => 'queued',
        ]);

        try {
            // Send via WAHA
            if ($this->campaign->media_path) {
                $result = $wahaService->sendImage(
                    $this->session,
                    $this->contact->phone,
                    $this->campaign->media_path,
                    $messageText
                );
            } else {
                $result = $wahaService->sendText(
                    $this->session,
                    $this->contact->phone,
                    $messageText
                );
            }

            if ($result['success']) {
                $message->update([
                    'status' => 'sent',
                    'waha_message_id' => $result['data']['id'] ?? null,
                    'sent_at' => now(),
                ]);

                $this->campaign->increment('sent_count');
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }

        } catch (\Exception $e) {
            Log::error('Campaign message failed', [
                'campaign' => $this->campaign->id,
                'contact' => $this->contact->id,
                'error' => $e->getMessage(),
            ]);

            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->campaign->increment('failed_count');

            throw $e; // Re-throw untuk retry
        }

        // Check if campaign completed
        $this->checkCampaignCompletion();
    }

    private function checkCampaignCompletion(): void
    {
        $total = $this->campaign->total_recipients;
        $processed = $this->campaign->sent_count + $this->campaign->failed_count;

        if ($processed >= $total) {
            $this->campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendCampaignMessageJob permanently failed', [
            'campaign' => $this->campaign->id,
            'contact' => $this->contact->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
