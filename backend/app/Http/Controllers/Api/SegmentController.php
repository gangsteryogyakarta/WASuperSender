<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Segment;
use App\Services\SegmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SegmentController extends Controller
{
    public function __construct(
        private SegmentService $segmentService
    ) {}

    /**
     * List all segments
     */
    public function index(): JsonResponse
    {
        $segments = Segment::withCount('contacts')
            ->orderBy('name')
            ->get();

        return response()->json($segments);
    }

    /**
     * Create new segment
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'criteria' => 'required|array',
            'criteria.*.field' => 'required|string',
            'criteria.*.operator' => 'nullable|string',
            'criteria.*.value' => 'required',
        ]);

        $segment = $this->segmentService->createSegment(
            $validated['name'],
            $validated['criteria'],
            $validated['description'] ?? null
        );

        return response()->json($segment, 201);
    }

    /**
     * Show segment with contacts
     */
    public function show(Segment $segment): JsonResponse
    {
        return response()->json(
            $segment->load(['contacts' => fn($q) => $q->limit(100)])
        );
    }

    /**
     * Update segment
     */
    public function update(Request $request, Segment $segment): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'criteria' => 'sometimes|array',
            'criteria.*.field' => 'required_with:criteria|string',
            'criteria.*.operator' => 'nullable|string',
            'criteria.*.value' => 'required_with:criteria',
        ]);

        $segment->update($validated);

        if (isset($validated['criteria'])) {
            $this->segmentService->syncSegmentContacts($segment);
        }

        return response()->json($segment);
    }

    /**
     * Sync segment contacts
     */
    public function sync(Segment $segment): JsonResponse
    {
        $count = $this->segmentService->syncSegmentContacts($segment);

        return response()->json([
            'message' => 'Segment synced',
            'contact_count' => $count,
        ]);
    }

    /**
     * Preview criteria (count without saving)
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'criteria' => 'required|array',
            'criteria.*.field' => 'required|string',
            'criteria.*.operator' => 'nullable|string',
            'criteria.*.value' => 'required',
        ]);

        $count = $this->segmentService->previewCount($validated['criteria']);

        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Delete segment
     */
    public function destroy(Segment $segment): JsonResponse
    {
        $segment->delete();
        return response()->json(['message' => 'Segment deleted']);
    }
}
