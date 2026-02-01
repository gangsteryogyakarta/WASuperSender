<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $campaignService
    ) {}

    /**
     * List all campaigns
     */
    public function index(Request $request): JsonResponse
    {
        $campaigns = Campaign::with('segment')
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($campaigns);
    }

    /**
     * Create new campaign
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message_template' => 'required|string',
            'media_path' => 'nullable|string',
            'scheduled_at' => 'nullable|date|after:now',
            'segment_id' => 'nullable|uuid|exists:segments,id',
        ]);

        $validated['created_by'] = $request->user()->id;

        $campaign = $this->campaignService->createCampaign($validated);

        return response()->json($campaign, 201);
    }

    /**
     * Show campaign with statistics
     */
    public function show(Campaign $campaign): JsonResponse
    {
        return response()->json([
            'campaign' => $campaign->load('segment'),
            'statistics' => $this->campaignService->getStatistics($campaign),
        ]);
    }

    /**
     * Update campaign (only draft/scheduled)
     */
    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return response()->json([
                'message' => 'Cannot update running or completed campaign'
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'message_template' => 'sometimes|string',
            'media_path' => 'nullable|string',
            'scheduled_at' => 'nullable|date|after:now',
            'segment_id' => 'nullable|uuid|exists:segments,id',
        ]);

        $campaign->update($validated);

        if (isset($validated['segment_id'])) {
            $this->campaignService->calculateRecipients($campaign);
        }

        return response()->json($campaign);
    }

    /**
     * Start campaign
     */
    public function start(Request $request, Campaign $campaign): JsonResponse
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return response()->json([
                'message' => 'Campaign cannot be started'
            ], 422);
        }

        $validated = $request->validate([
            'session' => 'required|string',
        ]);

        $this->campaignService->startCampaign($campaign, $validated['session']);

        return response()->json([
            'message' => 'Campaign started',
            'campaign' => $campaign->fresh(),
        ]);
    }

    /**
     * Pause campaign
     */
    public function pause(Campaign $campaign): JsonResponse
    {
        if ($campaign->status !== 'running') {
            return response()->json([
                'message' => 'Only running campaigns can be paused'
            ], 422);
        }

        $this->campaignService->pauseCampaign($campaign);

        return response()->json([
            'message' => 'Campaign paused',
            'campaign' => $campaign->fresh(),
        ]);
    }

    /**
     * Resume campaign
     */
    public function resume(Request $request, Campaign $campaign): JsonResponse
    {
        if ($campaign->status !== 'paused') {
            return response()->json([
                'message' => 'Only paused campaigns can be resumed'
            ], 422);
        }

        $validated = $request->validate([
            'session' => 'required|string',
        ]);

        $this->campaignService->resumeCampaign($campaign, $validated['session']);

        return response()->json([
            'message' => 'Campaign resumed',
            'campaign' => $campaign->fresh(),
        ]);
    }

    /**
     * Delete campaign (only draft)
     */
    public function destroy(Campaign $campaign): JsonResponse
    {
        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft campaigns can be deleted'
            ], 422);
        }

        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted']);
    }
}
