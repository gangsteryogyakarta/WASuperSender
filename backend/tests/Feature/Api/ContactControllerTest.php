<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_can_list_contacts(): void
    {
        Contact::factory()->count(5)->create();

        $response = $this->getJson('/api/contacts');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_contacts_by_lead_status(): void
    {
        Contact::factory()->count(3)->create(['lead_status' => 'new']);
        Contact::factory()->count(2)->create(['lead_status' => 'qualified']);

        $response = $this->getJson('/api/contacts?lead_status=new');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_search_contacts(): void
    {
        Contact::factory()->create(['name' => 'John Doe', 'phone' => '08123456789']);
        Contact::factory()->create(['name' => 'Jane Smith', 'phone' => '08987654321']);

        $response = $this->getJson('/api/contacts?search=John');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Doe');
    }

    public function test_can_create_contact(): void
    {
        $response = $this->postJson('/api/contacts', [
            'phone' => '08123456789',
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'vehicle_interest' => 'Toyota Avanza',
            'budget' => 250000000,
            'source' => 'showroom',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Test Customer');

        $this->assertDatabaseHas('contacts', [
            'phone' => '08123456789',
            'name' => 'Test Customer',
        ]);
    }

    public function test_can_update_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->putJson("/api/contacts/{$contact->id}", [
            'name' => 'Updated Name',
            'lead_status' => 'qualified',
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Updated Name');
    }

    public function test_can_update_lead_status(): void
    {
        $contact = Contact::factory()->create(['lead_status' => 'new']);

        $response = $this->patchJson("/api/contacts/{$contact->id}/status", [
            'lead_status' => 'contacted',
        ]);

        $response->assertOk()
            ->assertJsonPath('lead_status', 'contacted');
    }

    public function test_can_import_contacts(): void
    {
        $response = $this->postJson('/api/contacts/import', [
            'contacts' => [
                ['phone' => '08111111111', 'name' => 'Customer 1'],
                ['phone' => '08222222222', 'name' => 'Customer 2'],
                ['phone' => '08333333333', 'name' => 'Customer 3'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('imported', 3);

        $this->assertDatabaseCount('contacts', 3);
    }

    public function test_can_delete_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/contacts/{$contact->id}");

        $response->assertOk();
        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }
}
