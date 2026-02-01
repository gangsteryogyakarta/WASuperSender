<?php

namespace App\Jobs;

use App\Models\ContactSequence;
use App\Models\Message;
use App\Services\CampaignService;
use App\Services\WahaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public ContactSequence $contactSequence,
        public string $session
    ) {}

    public function handle(WahaService $wahaService, CampaignService $campaignService): void
    {
        // Skip if sequence is not active
        if ($this->contactSequence->status !== 'active') {
            return;
        }

        $sequence = $this->contactSequence->sequence;
        $contact = $this->contactSequence->contact;
        $currentStep = $this->contactSequence->current_step;

        // Get current step
        $step = $sequence->steps()->where('step_order', $currentStep)->first();

        if (!$step) {
            // No more steps, complete sequence
            $this->contactSequence->update(['status' => 'completed']);
            return;
        }

        // Parse message
        $messageText = $campaignService->parseTemplate($step->message_template, $contact);

        // Create message record
        $message = Message::create([
            'contact_id' => $contact->id,
            'direction' => 'outbound',
            'content' => $messageText,
            'status' => 'queued',
        ]);

        try {
            if ($step->media_path) {
                $result = $wahaService->sendImage(
                    $this->session,
                    $contact->phone,
                    $step->media_path,
                    $messageText
                );
            } else {
                $result = $wahaService->sendText(
                    $this->session,
                    $contact->phone,
                    $messageText
                );
            }

            if ($result['success']) {
                $message->update([
                    'status' => 'sent',
                    'waha_message_id' => $result['data']['id'] ?? null,
                    'sent_at' => now(),
                ]);

                // Move to next step
                $nextStep = $sequence->steps()->where('step_order', $currentStep + 1)->first();

                if ($nextStep) {
                    $this->contactSequence->update([
                        'current_step' => $currentStep + 1,
                        'next_run_at' => now()->addHours($nextStep->delay_hours),
                    ]);

                    // Schedule next step
                    self::dispatch($this->contactSequence, $this->session)
                        ->delay(now()->addHours($nextStep->delay_hours));
                } else {
                    $this->contactSequence->update(['status' => 'completed']);
                }
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }

        } catch (\Exception $e) {
            Log::error('Follow-up message failed', [
                'sequence' => $sequence->id,
                'contact' => $contact->id,
                'error' => $e->getMessage(),
            ]);

            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
