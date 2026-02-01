<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\SegmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    /**
     * List all contacts with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = Contact::with('segments');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by lead status
        if ($status = $request->input('lead_status')) {
            $query->where('lead_status', $status);
        }

        // Filter by vehicle interest
        if ($vehicle = $request->input('vehicle_interest')) {
            $query->where('vehicle_interest', 'like', "%{$vehicle}%");
        }

        // Filter by source
        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        // Filter by assigned sales
        if ($assignedTo = $request->input('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        $contacts = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($contacts);
    }

    /**
     * Create new contact
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'lead_status' => 'nullable|in:new,contacted,qualified,proposal,negotiation,closed_won,closed_lost',
            'vehicle_interest' => 'nullable|string|max:255',
            'budget' => 'nullable|numeric|min:0',
            'source' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'assigned_to' => 'nullable|uuid|exists:users,id',
        ]);

        $contact = Contact::create($validated);

        return response()->json($contact, 201);
    }

    /**
     * Show single contact
     */
    public function show(Contact $contact): JsonResponse
    {
        return response()->json(
            $contact->load(['segments', 'messages' => fn($q) => $q->latest()->limit(50)])
        );
    }

    /**
     * Update contact
     */
    public function update(Request $request, Contact $contact): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'sometimes|string|max:20',
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'lead_status' => 'nullable|in:new,contacted,qualified,proposal,negotiation,closed_won,closed_lost',
            'vehicle_interest' => 'nullable|string|max:255',
            'budget' => 'nullable|numeric|min:0',
            'source' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'assigned_to' => 'nullable|uuid|exists:users,id',
        ]);

        $contact->update($validated);

        return response()->json($contact);
    }

    /**
     * Delete contact
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();
        return response()->json(['message' => 'Contact deleted']);
    }

    /**
     * Import contacts from CSV/Excel data
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contacts' => 'required|array',
            'contacts.*.phone' => 'required|string',
            'contacts.*.name' => 'required|string',
            'contacts.*.email' => 'nullable|email',
            'contacts.*.vehicle_interest' => 'nullable|string',
            'contacts.*.budget' => 'nullable|numeric',
            'contacts.*.source' => 'nullable|string',
        ]);

        $imported = 0;
        $updated = 0;

        foreach ($validated['contacts'] as $data) {
            $contact = Contact::updateOrCreate(
                ['phone' => $data['phone']],
                $data
            );

            if ($contact->wasRecentlyCreated) {
                $imported++;
            } else {
                $updated++;
            }
        }

        return response()->json([
            'message' => 'Import completed',
            'imported' => $imported,
            'updated' => $updated,
        ]);
    }

    /**
     * Update lead status
     */
    public function updateStatus(Request $request, Contact $contact): JsonResponse
    {
        $validated = $request->validate([
            'lead_status' => 'required|in:new,contacted,qualified,proposal,negotiation,closed_won,closed_lost',
        ]);

        $contact->update($validated);

        return response()->json($contact);
    }
}
