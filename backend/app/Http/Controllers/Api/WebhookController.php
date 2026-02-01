<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\WahaSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming WAHA webhook
     */
    public function handle(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $payload = $request->input('payload', []);
        $session = $request->input('session');

        Log::info('WAHA webhook received', [
            'event' => $event,
            'session' => $session,
        ]);

        try {
            match ($event) {
                'message' => $this->handleIncomingMessage($payload, $session),
                'message.ack' => $this->handleMessageAck($payload),
                'session.status' => $this->handleSessionStatus($payload, $session),
                default => Log::warning("Unknown webhook event: {$event}"),
            };
        } catch (\Exception $e) {
            Log::error('Webhook handling failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle incoming message
     */
    private function handleIncomingMessage(array $payload, string $session): void
    {
        $from = $payload['from'] ?? null;
        $body = $payload['body'] ?? '';
        $messageId = $payload['id'] ?? null;

        if (!$from) {
            return;
        }

        // Extract phone number from chatId
        $phone = str_replace('@c.us', '', $from);

        // Find or create contact
        $contact = \App\Models\Contact::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => $payload['notifyName'] ?? 'Unknown',
                'source' => 'whatsapp_inbound',
                'lead_status' => 'new',
            ]
        );

        // Save incoming message
        Message::create([
            'contact_id' => $contact->id,
            'direction' => 'inbound',
            'content' => $body,
            'media_type' => $payload['type'] ?? 'chat',
            'waha_message_id' => $messageId,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        // Update contact last seen
        $contact->touch();

        Log::info('Incoming message saved', [
            'contact' => $contact->id,
            'phone' => $phone,
        ]);
    }

    /**
     * Handle message acknowledgment (delivery/read status)
     */
    private function handleMessageAck(array $payload): void
    {
        $messageId = $payload['id'] ?? null;
        $ack = $payload['ack'] ?? 0;

        if (!$messageId) {
            return;
        }

        $message = Message::where('waha_message_id', $messageId)->first();

        if (!$message) {
            return;
        }

        $updates = [];

        // ACK values: 1=sent, 2=delivered, 3=read
        switch ($ack) {
            case 1:
                if ($message->status !== 'delivered' && $message->status !== 'read') {
                    $updates['status'] = 'sent';
                }
                break;
            case 2:
                if ($message->status !== 'read') {
                    $updates['status'] = 'delivered';
                    $updates['delivered_at'] = now();
                }
                // Update campaign stats
                if ($message->campaign_id) {
                    $message->campaign->increment('delivered_count');
                }
                break;
            case 3:
                $updates['status'] = 'read';
                $updates['read_at'] = now();
                // Update campaign stats
                if ($message->campaign_id) {
                    $message->campaign->increment('read_count');
                }
                break;
        }

        if (!empty($updates)) {
            $message->update($updates);
        }
    }

    /**
     * Handle session status changes
     */
    private function handleSessionStatus(array $payload, string $session): void
    {
        $status = $payload['status'] ?? null;

        if (!$status) {
            return;
        }

        WahaSession::where('session_name', $session)->update([
            'status' => strtolower($status),
            'last_seen_at' => now(),
        ]);

        Log::info('Session status updated', [
            'session' => $session,
            'status' => $status,
        ]);
    }
}
