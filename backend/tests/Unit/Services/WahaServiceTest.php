<?php

namespace Tests\Unit\Services;

use App\Services\WahaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class WahaServiceTest extends TestCase
{
    private WahaService $wahaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wahaService = new WahaService();
        RateLimiter::clear('waha:send');
    }

    public function test_check_health_returns_true_when_server_is_healthy(): void
    {
        Http::fake([
            '*/api/health' => Http::response(['status' => 'ok'], 200),
        ]);

        $result = $this->wahaService->checkHealth();

        $this->assertTrue($result);
    }

    public function test_check_health_returns_false_when_server_is_down(): void
    {
        Http::fake([
            '*/api/health' => Http::response(null, 500),
        ]);

        $result = $this->wahaService->checkHealth();

        $this->assertFalse($result);
    }

    public function test_send_text_returns_success_response(): void
    {
        Http::fake([
            '*/api/sendText' => Http::response([
                'id' => 'msg_123',
                'status' => 'sent',
            ], 200),
        ]);

        $result = $this->wahaService->sendText('default', '08123456789', 'Hello Test');

        $this->assertTrue($result['success']);
        $this->assertEquals('msg_123', $result['data']['id']);
    }

    public function test_send_text_formats_indonesian_phone_number(): void
    {
        Http::fake([
            '*/api/sendText' => Http::response(['id' => 'msg_123'], 200),
        ]);

        $this->wahaService->sendText('default', '08123456789', 'Test');

        Http::assertSent(function ($request) {
            return str_contains($request['chatId'], '628123456789@c.us');
        });
    }

    public function test_rate_limiter_blocks_excessive_requests(): void
    {
        Http::fake([
            '*/api/sendText' => Http::response(['id' => 'msg_123'], 200),
        ]);

        // Hit rate limit
        for ($i = 0; $i < 35; $i++) {
            RateLimiter::hit('waha:send', 60);
        }

        $result = $this->wahaService->sendText('default', '08123456789', 'Test');

        $this->assertFalse($result['success']);
        $this->assertStringContains('Rate limit exceeded', $result['error']);
    }

    public function test_check_number_exists_returns_boolean(): void
    {
        Http::fake([
            '*/api/contacts/check-exists*' => Http::response(['numberExists' => true], 200),
        ]);

        $result = $this->wahaService->checkNumberExists('default', '08123456789');

        $this->assertTrue($result);
    }

    public function test_create_session_stores_in_database(): void
    {
        Http::fake([
            '*/api/sessions' => Http::response(['name' => 'test-session'], 200),
        ]);

        $result = $this->wahaService->createSession('test-session');

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('waha_sessions', [
            'session_name' => 'test-session',
        ]);
    }
}
