<?php

namespace App\Services;

use App\Models\Message;
use App\Models\WahaSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

class WahaService
{
    private string $baseUrl;
    private string $apiKey;
    private array $rateLimits;
    private array $retryConfig;

    public function __construct()
    {
        $this->baseUrl = config('waha.base_url');
        $this->apiKey = config('waha.api_key');
        $this->rateLimits = config('waha.rate_limits');
        $this->retryConfig = config('waha.retry');
    }

    /**
     * Check if WAHA server is healthy
     */
    public function checkHealth(): bool
    {
        try {
            $response = $this->request('GET', '/api/health');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WAHA health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Create a new session
     */
    public function createSession(string $sessionName, array $config = []): array
    {
        $payload = array_merge([
            'name' => $sessionName,
            'start' => true,
        ], $config);

        $response = $this->request('POST', '/api/sessions', $payload);
        
        if ($response->successful()) {
            WahaSession::updateOrCreate(
                ['session_name' => $sessionName],
                ['status' => 'starting', 'config' => $config]
            );
        }

        return $this->handleResponse($response);
    }

    /**
     * Get session status
     */
    public function getSessionStatus(string $sessionName): array
    {
        $response = $this->request('GET', "/api/sessions/{$sessionName}");
        return $this->handleResponse($response);
    }

    /**
     * Get QR code for session
     */
    public function getQrCode(string $sessionName): ?string
    {
        $response = $this->request('GET', "/api/{$sessionName}/auth/qr", [], false);
        
        if ($response->successful()) {
            return $response->body();
        }
        
        return null;
    }

    /**
     * Delete a session
     */
    public function deleteSession(string $sessionName): array
    {
        $response = $this->request('DELETE', "/api/sessions/{$sessionName}");
        
        if ($response->successful()) {
            WahaSession::where('session_name', $sessionName)->delete();
        }

        return $this->handleResponse($response);
    }

    /**
     * Send text message with rate limiting
     */
    public function sendText(string $session, string $phone, string $text): array
    {
        return $this->withRateLimit(function () use ($session, $phone, $text) {
            $response = $this->request('POST', '/api/sendText', [
                'session' => $session,
                'chatId' => $this->formatPhoneNumber($phone),
                'text' => $text,
            ]);

            return $this->handleResponse($response);
        });
    }

    /**
     * Send image message
     */
    public function sendImage(string $session, string $phone, string $mediaUrl, ?string $caption = null): array
    {
        return $this->withRateLimit(function () use ($session, $phone, $mediaUrl, $caption) {
            $payload = [
                'session' => $session,
                'chatId' => $this->formatPhoneNumber($phone),
                'file' => ['url' => $mediaUrl],
            ];

            if ($caption) {
                $payload['caption'] = $caption;
            }

            $response = $this->request('POST', '/api/sendImage', $payload);
            return $this->handleResponse($response);
        });
    }

    /**
     * Send file/document
     */
    public function sendFile(string $session, string $phone, string $mediaUrl, ?string $filename = null): array
    {
        return $this->withRateLimit(function () use ($session, $phone, $mediaUrl, $filename) {
            $payload = [
                'session' => $session,
                'chatId' => $this->formatPhoneNumber($phone),
                'file' => ['url' => $mediaUrl],
            ];

            if ($filename) {
                $payload['file']['filename'] = $filename;
            }

            $response = $this->request('POST', '/api/sendFile', $payload);
            return $this->handleResponse($response);
        });
    }

    /**
     * Check if phone number exists on WhatsApp
     */
    public function checkNumberExists(string $session, string $phone): bool
    {
        $response = $this->request('GET', '/api/contacts/check-exists', [
            'session' => $session,
            'phone' => $this->formatPhoneNumber($phone),
        ]);

        if ($response->successful()) {
            return $response->json('numberExists', false);
        }

        return false;
    }

    /**
     * Get all sessions
     */
    public function getSessions(): array
    {
        $response = $this->request('GET', '/api/sessions');
        return $this->handleResponse($response);
    }

    /**
     * Format phone number for WhatsApp
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle Indonesian numbers
        if (str_starts_with($phone, '08')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }
        
        return $phone . '@c.us';
    }

    /**
     * Execute with rate limiting
     */
    private function withRateLimit(callable $callback): array
    {
        $key = 'waha:send';
        $maxAttempts = $this->rateLimits['messages_per_minute'];
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return [
                'success' => false,
                'error' => "Rate limit exceeded. Try again in {$seconds} seconds.",
                'retry_after' => $seconds,
            ];
        }

        RateLimiter::hit($key, $decaySeconds);
        
        // Add delay between messages
        usleep($this->rateLimits['delay_between_messages'] * 1000000);

        return $callback();
    }

    /**
     * Make HTTP request to WAHA
     */
    private function request(string $method, string $endpoint, array $data = [], bool $json = true): Response
    {
        $client = Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->retry($this->retryConfig['max_attempts'], function ($attempt) {
                return $this->retryConfig['backoff_seconds'][$attempt - 1] * 1000;
            });

        if ($this->apiKey) {
            $client->withHeaders(['X-Api-Key' => $this->apiKey]);
        }

        return match (strtoupper($method)) {
            'GET' => $client->get($endpoint, $data),
            'POST' => $json ? $client->post($endpoint, $data) : $client->post($endpoint, $data),
            'PUT' => $client->put($endpoint, $data),
            'DELETE' => $client->delete($endpoint),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Handle API response
     */
    private function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }

        Log::error('WAHA API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'success' => false,
            'error' => $response->json('message', 'Unknown error'),
            'status' => $response->status(),
        ];
    }
}
