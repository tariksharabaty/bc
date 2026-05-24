<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\PiggyBank;
use App\Filament\Resources\PiggyBanks\PiggyBankResource;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class DummySahaOverhaulTableComponent extends \Livewire\Component implements \Filament\Tables\Contracts\HasTable, \Filament\Schemas\Contracts\HasSchemas
{
    use \Filament\Tables\Concerns\InteractsWithTable;
    use \Filament\Schemas\Concerns\InteractsWithSchemas;

    public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
    {
        return null;
    }
}

class SahaPanelOverhaulTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the assigned_to_user_id field is hidden from the form in the Saha panel context.
     */
    public function test_assigned_to_user_id_field_is_hidden_in_saha_panel(): void
    {
        $agent = User::create([
            'name' => 'Saha Görevlisi',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);

        $this->actingAs($agent);

        // Mock being on the 'saha' panel
        filament()->setCurrentPanel(filament()->getPanel('saha'));

        Livewire::actingAs($agent)
            ->test(\App\Filament\Resources\PiggyBanks\Pages\CreatePiggyBank::class)
            ->assertFormFieldHidden('assigned_to_user_id');
    }

    /**
     * Test that creating a piggy bank through the Saha panel successfully auto-assigns the record to the currently authenticated agent.
     */
    public function test_create_piggy_bank_auto_assigns_to_authenticated_agent_in_saha(): void
    {
        $agent = User::create([
            'name' => 'Saha Görevlisi',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);

        $shop = Shop::create([
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'name' => 'Test Market',
            'is_active' => true,
        ]);

        $this->actingAs($agent);

        // Mock being on the 'saha' panel
        filament()->setCurrentPanel(filament()->getPanel('saha'));

        Livewire::actingAs($agent)
            ->test(\App\Filament\Resources\PiggyBanks\Pages\CreatePiggyBank::class)
            ->fillForm([
                'shop_id' => $shop->id,
                'name' => 'Saha Kumbara',
                'current_balance' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('piggy_banks', [
            'name' => 'Saha Kumbara',
            'assigned_to_user_id' => $agent->id,
        ]);
    }

    /**
     * Test that delete and export actions are excluded/hidden from pages when using the Saha panel.
     */
    public function test_delete_and_export_actions_hidden_in_saha_panel(): void
    {
        $agent = User::create([
            'name' => 'Saha Görevlisi',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);

        $shop = Shop::create([
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'name' => 'Test Market',
            'is_active' => true,
        ]);

        $piggyBank = PiggyBank::create([
            'unique_box_id' => 'KMB-TEST-SAHA',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => $agent->id,
            'name' => 'Saha Box',
            'current_balance' => 100.0,
        ]);

        $this->actingAs($agent);

        // Mock being on the 'saha' panel
        filament()->setCurrentPanel(filament()->getPanel('saha'));

        // Delete action hidden on Edit page
        Livewire::actingAs($agent)
            ->test(\App\Filament\Resources\PiggyBanks\Pages\EditPiggyBank::class, [
                'record' => $piggyBank->getKey(),
            ])
            ->assertActionHidden('delete');

        // Export action hidden on List page
        Livewire::actingAs($agent)
            ->test(\App\Filament\Resources\PiggyBanks\Pages\ListPiggyBanks::class)
            ->assertActionHidden('export');
    }

    /**
     * Test that the status column computes and formats its state based on current balance.
     */
    public function test_dynamic_status_badge_displays_correctly(): void
    {
        $shop = Shop::create([
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'name' => 'Test Shop',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Agent',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);

        $activeBox = PiggyBank::create([
            'unique_box_id' => 'KMB-ACTIVE',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => $user->id,
            'name' => 'Active Box',
            'current_balance' => 500, // < 1000
        ]);

        $fullBox = PiggyBank::create([
            'unique_box_id' => 'KMB-FULL',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => $user->id,
            'name' => 'Full Box',
            'current_balance' => 1200, // >= 1000
        ]);

        $this->actingAs($user);

        $table = PiggyBankResource::table(\Filament\Tables\Table::make(new DummySahaOverhaulTableComponent()));

        $statusColumn = $table->getColumn('status');
        $this->assertNotNull($statusColumn, 'Status column was not registered on PiggyBankResource table.');

        $reflector = new \ReflectionClass($statusColumn);
        $property = $reflector->getProperty('getStateUsing');
        $property->setAccessible(true);
        $statusClosure = $property->getValue($statusColumn);

        $this->assertEquals(__('system.active'), $statusClosure($activeBox));
        $this->assertEquals(__('system.full'), $statusClosure($fullBox));
    }
}
