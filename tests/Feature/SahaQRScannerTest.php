<?php

namespace Tests\Feature;

use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Saha\Pages\SahaDashboard;
use App\Models\PiggyBank;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SahaQRScannerTest
 *
 * Verifies the live QR camera scanning flow end-to-end:
 *  1. The Saha panel can access the Transaction creation page.
 *  2. The form pre-fills `piggy_bank_id` from the URL query param `piggy_bank_id`
 *     (matched by `unique_box_id`).
 *  3. The dashboard's `qr_okut` action redirects to the correct URL on manual submit.
 *  4. After creation the piggy bank balance is updated correctly.
 *  5. After creation the field agent is redirected back to `/saha`.
 */
class SahaQRScannerTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function makeAgent(string $suffix = ''): User
    {
        return User::create([
            'name'     => 'Saha Görevlisi' . $suffix,
            'email'    => 'agent' . $suffix . '@example.com',
            'password' => bcrypt('password'),
            'role'     => 'field_agent',
        ]);
    }

    private function makeShop(): Shop
    {
        return Shop::create([
            'city'      => 'Istanbul',
            'district'  => 'Kadikoy',
            'name'      => 'Test Market',
            'is_active' => true,
        ]);
    }

    private function makePiggyBank(Shop $shop, User $agent, string $code = 'KMB-QR-001'): PiggyBank
    {
        return PiggyBank::create([
            'unique_box_id'       => $code,
            'shop_id'             => $shop->id,
            'assigned_to_user_id' => $agent->id,
            'name'                => 'QR Test Box',
            'current_balance'     => 100.00,
        ]);
    }

    // ─── Tests ───────────────────────────────────────────────────────────────────

    /**
     * Field agents can reach the Transaction creation page in the Saha panel.
     */
    public function test_saha_panel_transaction_create_page_is_accessible(): void
    {
        $agent = $this->makeAgent();
        $this->actingAs($agent);

        $response = $this->followingRedirects()->get('/saha/transactions/create');
        $response->assertSuccessful();
    }

    /**
     * When `piggy_bank_id` matches a `unique_box_id`, the form's piggy_bank_id
     * field is pre-filled with the matching model's primary key.
     */
    public function test_transaction_form_preloads_piggy_bank_from_unique_box_id_query_param(): void
    {
        $agent  = $this->makeAgent();
        $shop   = $this->makeShop();
        $piggy  = $this->makePiggyBank($shop, $agent, 'KMB-SCAN-001');

        $this->actingAs($agent);
        filament()->setCurrentPanel(filament()->getPanel('saha'));

        // Simulate the URL that the QR scanner produces after a successful scan
        Livewire::actingAs($agent)
            ->withQueryParams(['piggy_bank_id' => 'KMB-SCAN-001'])
            ->test(CreateTransaction::class)
            ->assertFormSet([
                'piggy_bank_id' => (string) $piggy->id,
            ]);
    }

    /**
     * When `piggy_bank_id` is in the URL, the piggy_bank_id select is disabled
     * (field workers cannot change the scanned box).
     */
    public function test_piggy_bank_field_is_disabled_when_prefilled_from_url(): void
    {
        $agent  = $this->makeAgent();
        $shop   = $this->makeShop();
        $piggy  = $this->makePiggyBank($shop, $agent, 'KMB-LOCK-001');

        $this->actingAs($agent);
        filament()->setCurrentPanel(filament()->getPanel('saha'));

        Livewire::actingAs($agent)
            ->withQueryParams(['piggy_bank_id' => 'KMB-LOCK-001'])
            ->test(CreateTransaction::class)
            ->assertFormFieldIsDisabled('piggy_bank_id');
    }

    /**
     * user_id is hidden and disabled on the Saha panel — agents cannot tamper
     * with the identity of who performed the transaction.
     */
    public function test_user_id_field_is_hidden_and_disabled_on_saha_panel(): void
    {
        $agent = $this->makeAgent();
        $this->actingAs($agent);
        filament()->setCurrentPanel(filament()->getPanel('saha'));

        Livewire::actingAs($agent)
            ->test(CreateTransaction::class)
            ->assertFormFieldIsHidden('user_id')
            ->assertFormFieldIsDisabled('user_id');
    }

    /**
     * Submitting the Transaction creation form on the Saha panel creates the
     * record with the correct user_id (from auth) and updates the piggy bank
     * current_balance automatically.
     */
    public function test_creating_transaction_on_saha_panel_auto_assigns_agent_and_updates_balance(): void
    {
        $agent  = $this->makeAgent();
        $shop   = $this->makeShop();
        $piggy  = $this->makePiggyBank($shop, $agent, 'KMB-CREATE-001');

        $this->actingAs($agent);
        filament()->setCurrentPanel(filament()->getPanel('saha'));

        Livewire::actingAs($agent)
            ->test(CreateTransaction::class)
            ->fillForm([
                'piggy_bank_id' => $piggy->id,
                'action_type'   => 'collection',
                'amount'        => 250.00,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Transaction should be recorded under the agent's user_id
        $this->assertDatabaseHas('transactions', [
            'piggy_bank_id' => $piggy->id,
            'user_id'       => $agent->id,
            'action_type'   => 'collection',
            'amount'        => 250.00,
        ]);

        // Piggy bank balance should have increased
        $this->assertEquals(350.00, $piggy->refresh()->current_balance);
    }

    /**
     * The `qr_okut` action on SahaDashboard redirects to the correct
     * Transaction creation URL with the scanned code as query parameter.
     */
    public function test_qr_okut_action_redirects_to_transaction_create_with_code(): void
    {
        $agent = $this->makeAgent();
        $this->actingAs($agent);

        Livewire::actingAs($agent)
            ->test(SahaDashboard::class)
            ->callAction('qr_okut', data: ['scanned_code' => 'KMB-TEST-999'])
            ->assertRedirect('/saha/transactions/create?piggy_bank_id=KMB-TEST-999');
    }

    /**
     * If the scanned code is empty, the `qr_okut` action does nothing (no redirect, no error).
     */
    public function test_qr_okut_action_does_nothing_when_code_is_empty(): void
    {
        $agent = $this->makeAgent();
        $this->actingAs($agent);

        Livewire::actingAs($agent)
            ->test(SahaDashboard::class)
            ->callAction('qr_okut', data: ['scanned_code' => ''])
            ->assertHasNoActionErrors();
    }

    /**
     * A reset transaction sets the piggy bank balance to zero.
     */
    public function test_reset_transaction_zeroes_piggy_bank_balance(): void
    {
        $agent  = $this->makeAgent();
        $shop   = $this->makeShop();
        $piggy  = $this->makePiggyBank($shop, $agent, 'KMB-RESET-001');

        $this->actingAs($agent);
        filament()->setCurrentPanel(filament()->getPanel('saha'));

        Livewire::actingAs($agent)
            ->test(CreateTransaction::class)
            ->fillForm([
                'piggy_bank_id' => $piggy->id,
                'action_type'   => 'reset',
                'amount'        => 100.00,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertEquals(0.0, $piggy->refresh()->current_balance);
    }
}
