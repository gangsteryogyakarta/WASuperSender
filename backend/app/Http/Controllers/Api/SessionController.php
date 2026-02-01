<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WahaSession;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function __construct(
        private WahaService $wahaService
    ) {}

    /**
     * List all sessions
     */
    public function index(): JsonResponse
    {
        $localSessions = WahaSession::all();
        $wahaResult = $this->wahaService->getSessions();

        return response()->json([
            'local_sessions' => $localSessions,
            'waha_sessions' => $wahaResult['success'] ? $wahaResult['data'] : [],
        ]);
    }

    /**
     * Create new session
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_name' => 'required|string|max:50|unique:waha_sessions,session_name',
        ]);

        $result = $this->wahaService->createSession($validated['session_name']);

        if (!$result['success']) {
            return response()->json([
                'message' => 'Failed to create session',
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);
        }

        return response()->json([
            'message' => 'Session created',
            'session' => WahaSession::where('session_name', $validated['session_name'])->first(),
        ], 201);
    }

    /**
     * Get session status and QR code
     */
    public function show(string $sessionName): JsonResponse
    {
        $session = WahaSession::where('session_name', $sessionName)->first();
        $statusResult = $this->wahaService->getSessionStatus($sessionName);

        $response = [
            'local_session' => $session,
            'waha_status' => $statusResult['success'] ? $statusResult['data'] : null,
        ];

        // Get QR code if session needs scanning
        if ($session && $session->status === 'scan_qr_code') {
            $qrCode = $this->wahaService->getQrCode($sessionName);
            $response['qr_code'] = $qrCode;
        }

        return response()->json($response);
    }

    /**
     * Delete session
     */
    public function destroy(string $sessionName): JsonResponse
    {
        $result = $this->wahaService->deleteSession($sessionName);

        if (!$result['success']) {
            return response()->json([
                'message' => 'Failed to delete session',
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);
        }

        return response()->json(['message' => 'Session deleted']);
    }

    /**
     * Check WAHA server health
     */
    public function health(): JsonResponse
    {
        $isHealthy = $this->wahaService->checkHealth();

        return response()->json([
            'healthy' => $isHealthy,
            'waha_url' => config('waha.base_url'),
        ]);
    }

    /**
     * Check if phone number exists on WhatsApp
     */
    public function checkNumber(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session' => 'required|string',
            'phone' => 'required|string',
        ]);

        $exists = $this->wahaService->checkNumberExists(
            $validated['session'],
            $validated['phone']
        );

        return response()->json([
            'phone' => $validated['phone'],
            'exists' => $exists,
        ]);
    }
}
