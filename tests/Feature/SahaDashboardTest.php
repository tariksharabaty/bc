<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\PiggyBank;
use App\Models\Transaction;
use App\Filament\Saha\Pages\SahaDashboard;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SahaDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that field agents can successfully access the Saha Dashboard page.
     */
    public function test_saha_dashboard_is_accessible_to_field_agents(): void
    {
        $agent = User::create([
            'name' => 'Saha Görevlisi',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);
        $this->actingAs($agent);

        $response = $this->followingRedirects()->get('/saha');
        $response->assertSuccessful();
    }

    /**
     * Test that the dashboard strictly scopes Piggy Banks to the logged-in agent.
     */
    public function test_saha_dashboard_strictly_scopes_assigned_piggy_banks(): void
    {
        $agent1 = User::create([
            'name' => 'Saha Görevlisi 1',
            'email' => 'agent1@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);

        $agent2 = User::create([
            'name' => 'Saha Görevlisi 2',
            'email' => 'agent2@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);

        $shop = Shop::create([
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'name' => 'Test Market',
            'is_active' => true,
        ]);

        // Create box assigned to agent 1
        $box1 = PiggyBank::create([
            'unique_box_id' => 'KMB-001',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => $agent1->id,
            'name' => 'Box 1',
            'current_balance' => 100.0,
        ]);

        // Create box assigned to agent 2
        $box2 = PiggyBank::create([
            'unique_box_id' => 'KMB-002',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => $agent2->id,
            'name' => 'Box 2',
            'current_balance' => 200.0,
        ]);

        $this->actingAs($agent1);

        Livewire::test(SahaDashboard::class)
            ->assertSee('KMB-001')
            ->assertDontSee('KMB-002');
    }

    /**
     * Test that quick collections can be executed directly from the dashboard listing.
     */
    public function test_quick_collection_action_works_directly_from_dashboard(): void
    {
        $agent = User::create([
            'name' => 'Saha Görevlisi',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);
        $this->actingAs($agent);

        $shop = Shop::create([
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'name' => 'Test Market',
            'is_active' => true,
        ]);

        $piggy = PiggyBank::create([
            'unique_box_id' => 'KMB-999',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => $agent->id,
            'name' => 'Scanned Box',
            'current_balance' => 50.0,
        ]);

        // Trigger Livewire action 'tahsilat_ekle' with parameters
        Livewire::test(SahaDashboard::class)
            ->callAction('tahsilat_ekle', data: ['amount' => 150.00], arguments: ['piggy_bank_id' => $piggy->id])
            ->assertHasNoActionErrors();

        // Verify balance updated on piggy bank model
        $this->assertEquals(200.0, $piggy->refresh()->current_balance);

        // Verify transaction was generated under agent's name
        $this->assertDatabaseHas('transactions', [
            'piggy_bank_id' => $piggy->id,
            'user_id' => $agent->id,
            'action_type' => 'collection',
            'amount' => 150.0,
        ]);
    }
}
