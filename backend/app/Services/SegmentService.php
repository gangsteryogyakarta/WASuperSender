<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SegmentService
{
    /**
     * Build query based on segment criteria
     */
    public function buildQuery(array $criteria): Builder
    {
        $query = Contact::query();

        foreach ($criteria as $rule) {
            $this->applyRule($query, $rule);
        }

        return $query;
    }

    /**
     * Apply a single rule to the query
     */
    private function applyRule(Builder $query, array $rule): void
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? '=';
        $value = $rule['value'] ?? null;

        if (!$field || $value === null) {
            return;
        }

        match ($field) {
            'lead_status' => $query->where('lead_status', $operator, $value),
            'vehicle_interest' => $query->where('vehicle_interest', 'like', "%{$value}%"),
            'budget_min' => $query->where('budget', '>=', $value),
            'budget_max' => $query->where('budget', '<=', $value),
            'source' => $query->where('source', $operator, $value),
            'last_contact_before' => $query->where('updated_at', '<', Carbon::parse($value)),
            'last_contact_after' => $query->where('updated_at', '>', Carbon::parse($value)),
            'created_before' => $query->whereDate('created_at', '<', Carbon::parse($value)),
            'created_after' => $query->whereDate('created_at', '>', Carbon::parse($value)),
            'assigned_to' => $query->where('assigned_to', $value),
            'has_email' => $value ? $query->whereNotNull('email') : $query->whereNull('email'),
            default => null,
        };
    }

    /**
     * Sync contacts to a segment based on criteria
     */
    public function syncSegmentContacts(Segment $segment): int
    {
        $contacts = $this->buildQuery($segment->criteria)->pluck('id');
        
        $segment->contacts()->sync($contacts);
        $segment->update(['contact_count' => $contacts->count()]);

        return $contacts->count();
    }

    /**
     * Create a new segment with auto-sync
     */
    public function createSegment(string $name, array $criteria, ?string $description = null): Segment
    {
        $segment = Segment::create([
            'name' => $name,
            'description' => $description,
            'criteria' => $criteria,
        ]);

        $this->syncSegmentContacts($segment);

        return $segment;
    }

    /**
     * Get preview count for criteria without saving
     */
    public function previewCount(array $criteria): int
    {
        return $this->buildQuery($criteria)->count();
    }
}
