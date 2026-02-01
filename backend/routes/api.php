<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\SegmentController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public webhook endpoint (no auth required)
Route::post('/webhook/waha', [WebhookController::class, 'handle'])->name('webhook.waha');

// Health check (no auth)
Route::get('/health', [SessionController::class, 'health'])->name('health');

// Auth
use App\Http\Controllers\Api\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // User info
    Route::get('/user', fn(Request $request) => $request->user());

    // Contacts
    Route::prefix('contacts')->group(function () {
        Route::get('/', [ContactController::class, 'index']);
        Route::post('/', [ContactController::class, 'store']);
        Route::post('/import', [ContactController::class, 'import']);
        Route::get('/{contact}', [ContactController::class, 'show']);
        Route::put('/{contact}', [ContactController::class, 'update']);
        Route::patch('/{contact}/status', [ContactController::class, 'updateStatus']);
        Route::delete('/{contact}', [ContactController::class, 'destroy']);
    });

    // Segments
    Route::prefix('segments')->group(function () {
        Route::get('/', [SegmentController::class, 'index']);
        Route::post('/', [SegmentController::class, 'store']);
        Route::post('/preview', [SegmentController::class, 'preview']);
        Route::get('/{segment}', [SegmentController::class, 'show']);
        Route::put('/{segment}', [SegmentController::class, 'update']);
        Route::post('/{segment}/sync', [SegmentController::class, 'sync']);
        Route::delete('/{segment}', [SegmentController::class, 'destroy']);
    });

    // Campaigns
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::get('/{campaign}', [CampaignController::class, 'show']);
        Route::put('/{campaign}', [CampaignController::class, 'update']);
        Route::post('/{campaign}/start', [CampaignController::class, 'start']);
        Route::post('/{campaign}/pause', [CampaignController::class, 'pause']);
        Route::post('/{campaign}/resume', [CampaignController::class, 'resume']);
        Route::delete('/{campaign}', [CampaignController::class, 'destroy']);
    });

    // WAHA Sessions
    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index']);
        Route::post('/', [SessionController::class, 'store']);
        Route::get('/{sessionName}', [SessionController::class, 'show']);
        Route::delete('/{sessionName}', [SessionController::class, 'destroy']);
        Route::post('/check-number', [SessionController::class, 'checkNumber']);
    });

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('/lead-funnel', [AnalyticsController::class, 'leadFunnel']);
        Route::get('/messages', [AnalyticsController::class, 'messageStats']);
        Route::get('/campaigns', [AnalyticsController::class, 'campaignPerformance']);
        Route::get('/vehicle-interest', [AnalyticsController::class, 'vehicleInterest']);
        Route::get('/lead-sources', [AnalyticsController::class, 'leadSources']);
    });
});
