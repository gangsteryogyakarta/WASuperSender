<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\Message;
use App\Models\WahaSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_endpoint_returns_ok(): void
    {
        $response = $this->postJson('/api/webhook/waha', [
            'event' => 'unknown.event',
            'payload' => [],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_incoming_message_creates_contact(): void
    {
        $response = $this->postJson('/api/webhook/waha', [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'from' => '628123456789@c.us',
                'body' => 'Halo, saya tertarik dengan mobil',
                'id' => 'msg_incoming_123',
                'notifyName' => 'Prospek Baru',
                'type' => 'chat',
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('contacts', [
            'phone' => '628123456789',
            'name' => 'Prospek Baru',
            'source' => 'whatsapp_inbound',
        ]);

        $this->assertDatabaseHas('messages', [
            'direction' => 'inbound',
            'content' => 'Halo, saya tertarik dengan mobil',
        ]);
    }

    public function test_message_ack_updates_delivery_status(): void
    {
        $contact = Contact::factory()->create();
        $message = Message::create([
            'contact_id' => $contact->id,
            'direction' => 'outbound',
            'content' => 'Test message',
            'status' => 'sent',
            'waha_message_id' => 'msg_ack_test_123',
        ]);

        $response = $this->postJson('/api/webhook/waha', [
            'event' => 'message.ack',
            'payload' => [
                'id' => 'msg_ack_test_123',
                'ack' => 2, // delivered
            ],
        ]);

        $response->assertOk();

        $message->refresh();
        $this->assertEquals('delivered', $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    public function test_message_ack_updates_read_status(): void
    {
        $contact = Contact::factory()->create();
        $message = Message::create([
            'contact_id' => $contact->id,
            'direction' => 'outbound',
            'content' => 'Test message',
            'status' => 'delivered',
            'waha_message_id' => 'msg_read_test_123',
            'delivered_at' => now(),
        ]);

        $response = $this->postJson('/api/webhook/waha', [
            'event' => 'message.ack',
            'payload' => [
                'id' => 'msg_read_test_123',
                'ack' => 3, // read
            ],
        ]);

        $response->assertOk();

        $message->refresh();
        $this->assertEquals('read', $message->status);
        $this->assertNotNull($message->read_at);
    }

    public function test_session_status_updates_local_session(): void
    {
        WahaSession::create([
            'session_name' => 'test-session',
            'status' => 'starting',
        ]);

        $response = $this->postJson('/api/webhook/waha', [
            'event' => 'session.status',
            'session' => 'test-session',
            'payload' => [
                'status' => 'WORKING',
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('waha_sessions', [
            'session_name' => 'test-session',
            'status' => 'working',
        ]);
    }
}
