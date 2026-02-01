<?php

namespace Tests\Unit\Services;

use App\Models\Contact;
use App\Models\Segment;
use App\Services\SegmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private SegmentService $segmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->segmentService = new SegmentService();
    }

    public function test_build_query_filters_by_lead_status(): void
    {
        Contact::factory()->create(['lead_status' => 'new']);
        Contact::factory()->create(['lead_status' => 'qualified']);
        Contact::factory()->create(['lead_status' => 'new']);

        $criteria = [
            ['field' => 'lead_status', 'operator' => '=', 'value' => 'new'],
        ];

        $result = $this->segmentService->buildQuery($criteria)->get();

        $this->assertCount(2, $result);
    }

    public function test_build_query_filters_by_vehicle_interest(): void
    {
        Contact::factory()->create(['vehicle_interest' => 'Toyota Avanza']);
        Contact::factory()->create(['vehicle_interest' => 'Honda Civic']);
        Contact::factory()->create(['vehicle_interest' => 'Toyota Innova']);

        $criteria = [
            ['field' => 'vehicle_interest', 'value' => 'Toyota'],
        ];

        $result = $this->segmentService->buildQuery($criteria)->get();

        $this->assertCount(2, $result);
    }

    public function test_build_query_filters_by_budget_range(): void
    {
        Contact::factory()->create(['budget' => 100000000]);
        Contact::factory()->create(['budget' => 250000000]);
        Contact::factory()->create(['budget' => 500000000]);

        $criteria = [
            ['field' => 'budget_min', 'value' => 200000000],
            ['field' => 'budget_max', 'value' => 400000000],
        ];

        $result = $this->segmentService->buildQuery($criteria)->get();

        $this->assertCount(1, $result);
    }

    public function test_create_segment_auto_syncs_contacts(): void
    {
        Contact::factory()->count(5)->create(['lead_status' => 'new']);
        Contact::factory()->count(3)->create(['lead_status' => 'qualified']);

        $segment = $this->segmentService->createSegment(
            'New Leads',
            [['field' => 'lead_status', 'operator' => '=', 'value' => 'new']],
            'All new leads'
        );

        $this->assertEquals(5, $segment->contact_count);
        $this->assertCount(5, $segment->contacts);
    }

    public function test_preview_count_returns_correct_count(): void
    {
        Contact::factory()->count(10)->create(['source' => 'showroom']);
        Contact::factory()->count(5)->create(['source' => 'online']);

        $count = $this->segmentService->previewCount([
            ['field' => 'source', 'operator' => '=', 'value' => 'showroom'],
        ]);

        $this->assertEquals(10, $count);
    }

    public function test_sync_segment_contacts_updates_pivot(): void
    {
        $contacts = Contact::factory()->count(3)->create(['lead_status' => 'new']);
        
        $segment = Segment::factory()->create([
            'criteria' => [['field' => 'lead_status', 'operator' => '=', 'value' => 'new']],
        ]);

        $count = $this->segmentService->syncSegmentContacts($segment);

        $this->assertEquals(3, $count);
        $this->assertEquals(3, $segment->fresh()->contact_count);
    }
}
