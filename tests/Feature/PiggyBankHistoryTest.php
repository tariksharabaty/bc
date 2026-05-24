<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\PiggyBank;
use App\Models\Transaction;
use App\Filament\Resources\PiggyBanks\PiggyBankResource;
use App\Filament\Resources\PiggyBanks\Pages\ViewPiggyBank;
use App\Filament\Resources\PiggyBanks\RelationManagers\TransactionsRelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DummyHistoryTableComponent extends \Livewire\Component implements \Filament\Tables\Contracts\HasTable, \Filament\Schemas\Contracts\HasSchemas
{
    use \Filament\Tables\Concerns\InteractsWithTable;
    use \Filament\Schemas\Concerns\InteractsWithSchemas;

    public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
    {
        return null;
    }
}

class PiggyBankHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the PiggyBankResource register and mount the ViewRecord page correctly.
     * PiggyBankResource'un ViewRecord sayfasını başarıyla kaydettiğini ve açtığını test eder.
     */
    public function test_view_piggy_bank_page_is_accessible(): void
    {
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
        $this->actingAs($user);

        $shop = Shop::create([
            'city' => 'Istanbul',
            'district' => 'Uskudar',
            'name' => 'Test Shop',
            'is_active' => true,
        ]);

        $piggyBank = PiggyBank::create([
            'unique_box_id' => 'KMB-TEST',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => $user->id,
            'name' => 'Test Box',
            'current_balance' => 250.0,
        ]);

        // Generate the URL using Filament's native routing
        $url = PiggyBankResource::getUrl('view', ['record' => $piggyBank]);
        
        // Assert the page loads successfully (200 OK)
        $response = $this->get($url);
        $response->assertSuccessful();
        $response->assertSee('KMB-TEST');
        $response->assertSee('Test Shop');
    }

    /**
     * Test that the infolist configuration is properly defined and has the correct fields.
     * Infolist yapılandırmasının doğru tanımlandığını ve gerekli alanları içerdiğini test eder.
     */
    public function test_piggy_bank_infolist_has_correct_structure(): void
    {
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
        $this->actingAs($user);

        // Instantiate Dummy Livewire component
        $livewire = new DummyHistoryTableComponent();

        // Build the Infolist using the class-under-test method
        $infolist = PiggyBankResource::infolist(Schema::make($livewire));
        
        $components = $infolist->getComponents();
        $this->assertNotEmpty($components, 'The Infolist schema is empty.');

        $section = $components[0];
        $this->assertInstanceOf(\Filament\Schemas\Components\Section::class, $section);
        $this->assertStringContainsString('Kumbara Detayları', $section->getHeading());

        $fields = $section->getChildComponents();
        $this->assertCount(4, $fields, 'Expected 4 entry fields in the PiggyBank infolist.');

        // Verify key text entries exist
        $shopEntry = collect($fields)->firstWhere(fn ($f) => $f->getName() === 'shop.name');
        $userEntry = collect($fields)->firstWhere(fn ($f) => $f->getName() === 'user.name');
        $balanceEntry = collect($fields)->firstWhere(fn ($f) => $f->getName() === 'current_balance');
        $dateEntry = collect($fields)->firstWhere(fn ($f) => $f->getName() === 'created_at');

        $this->assertNotNull($shopEntry, 'shop.name entry is missing from infolist.');
        $this->assertNotNull($userEntry, 'user.name entry is missing from infolist.');
        $this->assertNotNull($balanceEntry, 'current_balance entry is missing from infolist.');
        $this->assertNotNull($dateEntry, 'created_at entry is missing from infolist.');
    }

    /**
     * Test that the TransactionsRelationManager contains the correct columns and sort order.
     * TransactionsRelationManager'ın doğru sütunları ve sıralamayı içerdiğini test eder.
     */
    public function test_transactions_relation_manager_configuration(): void
    {
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
        $this->actingAs($user);

        $shop = Shop::create([
            'city' => 'Istanbul',
            'district' => 'Uskudar',
            'name' => 'Test Shop',
            'is_active' => true,
        ]);

        $piggyBank = PiggyBank::create([
            'unique_box_id' => 'KMB-TEST',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => $user->id,
            'name' => 'Test Box',
            'current_balance' => 0.0,
        ]);

        $livewire = new DummyHistoryTableComponent();

        // Instantiate the Relation Manager
        $manager = new TransactionsRelationManager($livewire);
        $manager->ownerRecord = $piggyBank;
        $manager->pageClass = ViewPiggyBank::class;

        $table = $manager->table(\Filament\Tables\Table::make($livewire));

        // Assert that columns exist
        $columns = $table->getColumns();
        $this->assertArrayHasKey('created_at', $columns);
        $this->assertArrayHasKey('user.name', $columns);
        $this->assertArrayHasKey('action_type', $columns);
        $this->assertArrayHasKey('amount', $columns);

        // Assert sort order is default descending by created_at
        $this->assertEquals('created_at', $table->getDefaultSortColumn());
        $this->assertEquals('desc', $table->getDefaultSortDirection());
    }

    /**
     * Test that PiggyBankResource table includes the ViewAction.
     * PiggyBankResource tablosunun ViewAction (Görüntüle) içerdiğini test eder.
     */
    public function test_piggy_bank_resource_table_includes_view_action(): void
    {
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
        $this->actingAs($user);

        $livewire = new DummyHistoryTableComponent();
        $table = PiggyBankResource::table(\Filament\Tables\Table::make($livewire));

        $hasViewAction = false;
        foreach ($table->getActions() as $action) {
            if ($action instanceof ViewAction) {
                $hasViewAction = true;
                break;
            }
        }

        $this->assertTrue($hasViewAction, 'Table does not contain the ViewAction component.');
    }
}
