<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_can_list_campaigns(): void
    {
        Campaign::factory()->count(5)->create();

        $response = $this->getJson('/api/campaigns');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_can_create_campaign(): void
    {
        $segment = Segment::factory()->create();

        $response = $this->postJson('/api/campaigns', [
            'name' => 'Promo Akhir Tahun',
            'message_template' => 'Halo [Nama], ada promo spesial untuk Anda!',
            'segment_id' => $segment->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Promo Akhir Tahun');
    }

    public function test_can_schedule_campaign(): void
    {
        $response = $this->postJson('/api/campaigns', [
            'name' => 'Scheduled Campaign',
            'message_template' => 'Test message',
            'scheduled_at' => now()->addDay()->toISOString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'scheduled');
    }

    public function test_cannot_update_running_campaign(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'running']);

        $response = $this->putJson("/api/campaigns/{$campaign->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_pause_running_campaign(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'running']);

        $response = $this->postJson("/api/campaigns/{$campaign->id}/pause");

        $response->assertOk()
            ->assertJsonPath('campaign.status', 'paused');
    }

    public function test_cannot_delete_non_draft_campaign(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'completed']);

        $response = $this->deleteJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(422);
    }
}
