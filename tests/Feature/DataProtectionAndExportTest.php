<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\PiggyBank;
use App\Models\Transaction;
use App\Filament\Resources\Shops\ShopResource;
use App\Filament\Resources\PiggyBanks\PiggyBankResource;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Filament\Exports\ShopExporter;
use App\Filament\Exports\PiggyBankExporter;
use App\Filament\Exports\TransactionExporter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ExportBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DummyTableComponent extends \Livewire\Component implements \Filament\Tables\Contracts\HasTable
{
    use \Filament\Tables\Concerns\InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
    {
        return null;
    }
}

class DataProtectionAndExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test soft deletes are correctly configured on Shop, PiggyBank, and Transaction models.
     */
    public function test_models_support_soft_deletes(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

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

        $transaction = Transaction::create([
            'piggy_bank_id' => $piggyBank->id,
            'user_id' => $user->id,
            'action_type' => 'collection',
            'amount' => 100.0,
        ]);

        // Verify models use SoftDeletes and can be soft deleted
        $transaction->delete();
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);

        // Note: piggyBank has a transaction record, so deleting it should throw Exception
        $thrownPiggy = false;
        try {
            $piggyBank->delete();
        } catch (\Exception $e) {
            $thrownPiggy = true;
            $this->assertStringContainsString('tahsilat/sıfırlama', $e->getMessage());
        }
        $this->assertTrue($thrownPiggy, 'Expected an Exception when deleting PiggyBank with transactions.');

        // Shop also cannot be deleted due to active transactions under its piggy banks
        $thrownShop = false;
        try {
            $shop->delete();
        } catch (\Exception $e) {
            $thrownShop = true;
            $this->assertStringContainsString('tahsilat/sıfırlama', $e->getMessage());
        }
        $this->assertTrue($thrownShop, 'Expected an Exception when deleting Shop with transactions.');

        // Clean up transaction to allow deleting box and shop
        $transaction->forceDelete();

        // Now piggy bank should delete successfully (soft delete)
        $piggyBank->delete();
        $this->assertSoftDeleted('piggy_banks', ['id' => $piggyBank->id]);

        // Shop should also delete successfully (soft delete)
        $shop->delete();
        $this->assertSoftDeleted('shops', ['id' => $shop->id]);
    }

    /**
     * Test that Filament Resources have the TrashedFilter.
     */
    public function test_filament_resources_have_trashed_filter(): void
    {
        // Setup mock user session since resources might check auth user
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
        $this->actingAs($user);

        // Instantiate Dummy Livewire component
        $livewire = new DummyTableComponent();

        // Instantiate Filament Table builder
        $tableShop = ShopResource::table(\Filament\Tables\Table::make($livewire));
        $tablePiggy = PiggyBankResource::table(\Filament\Tables\Table::make($livewire));
        $tableTrans = TransactionResource::table(\Filament\Tables\Table::make($livewire));

        // Assert TrashedFilter exists in filters
        $this->assertHasFilter($tableShop, TrashedFilter::class);
        $this->assertHasFilter($tablePiggy, TrashedFilter::class);
        $this->assertHasFilter($tableTrans, TrashedFilter::class);
    }

    /**
     * Test that Filament Resources have the ExportBulkAction.
     */
    public function test_filament_resources_have_export_bulk_action(): void
    {
        // Setup admin user session
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
        $this->actingAs($user);

        $livewire = new DummyTableComponent();

        $tableShop = ShopResource::table(\Filament\Tables\Table::make($livewire));
        $tablePiggy = PiggyBankResource::table(\Filament\Tables\Table::make($livewire));
        $tableTrans = TransactionResource::table(\Filament\Tables\Table::make($livewire));

        // Assert ExportBulkAction exists in bulkActions
        $this->assertHasBulkAction($tableShop, \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::class);
        $this->assertHasBulkAction($tablePiggy, \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::class);
        $this->assertHasBulkAction($tableTrans, \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::class);
    }

    /**
     * Helper asserting that a table has a specific filter class.
     */
    protected function assertHasFilter(\Filament\Tables\Table $table, string $filterClass): void
    {
        $hasFilter = false;
        foreach ($table->getFilters() as $filter) {
            if ($filter instanceof $filterClass) {
                $hasFilter = true;
                break;
            }
        }
        $this->assertTrue($hasFilter, "Table does not contain filter: {$filterClass}");
    }

    /**
     * Helper asserting that a table has a specific bulk action class.
     */
    protected function assertHasBulkAction(\Filament\Tables\Table $table, string $actionClass): void
    {
        $hasAction = false;
        $flatActions = [];
        
        foreach ($table->getBulkActions() as $action) {
            if ($action instanceof \Filament\Actions\BulkActionGroup || $action instanceof \Filament\Tables\Actions\BulkActionGroup) {
                foreach ($action->getActions() as $child) {
                    $flatActions[] = $child;
                }
            } else {
                $flatActions[] = $action;
            }
        }

        foreach ($flatActions as $action) {
            if ($action instanceof $actionClass) {
                $hasAction = true;
                break;
            }
        }
        $this->assertTrue($hasAction, "Table does not contain bulk action: {$actionClass}");
    }
}
