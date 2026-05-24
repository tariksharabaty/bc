<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\PiggyBank;
use App\Models\Transaction;
use App\Filament\Resources\Shops\Pages\ListShops;
use App\Filament\Resources\PiggyBanks\Pages\ListPiggyBanks;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Exports\ShopExporter;
use App\Filament\Exports\PiggyBankExporter;
use App\Filament\Exports\TransactionExporter;
use Filament\Actions\ExportAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalFooterAndExplicitExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that AboutSystemWidget has been completely removed from the filesystem and classes.
     */
    public function test_about_system_widget_is_completely_removed(): void
    {
        $this->assertFileDoesNotExist(
            app_path('Filament/Widgets/AboutSystemWidget.php'),
            'AboutSystemWidget class file should not exist.'
        );

        $this->assertFileDoesNotExist(
            resource_path('views/filament/widgets/about-system-widget.blade.php'),
            'AboutSystemWidget blade view should not exist.'
        );
    }

    /**
     * Test that the modern, sleek Global Footer renders on the Admin Dashboard page.
     */
    public function test_global_footer_renders_on_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin);

        // Access the Admin Dashboard page
        $response = $this->get('/admin');

        $response->assertSee('Kumbara Takip Sistemi');
        $response->assertSee('Sygrad');
        $response->assertSee(__('messages.all_rights_reserved'));
        $response->assertSee('&copy; 2026 Sygrad. ' . __('messages.all_rights_reserved'), false);
    }

    /**
     * Test that the modern, sleek Global Footer renders on the Saha Dashboard page.
     */
    public function test_global_footer_renders_on_saha_dashboard(): void
    {
        $fieldUser = User::create([
            'name' => 'Saha Kullanıcısı',
            'email' => 'saha@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);

        $this->actingAs($fieldUser);

        // Access the Saha Dashboard page
        $response = $this->followingRedirects()->get('/saha');

        $response->assertSee('Kumbara Takip Sistemi');
        $response->assertSee('Sygrad');
        $response->assertSee(__('messages.all_rights_reserved'));
        $response->assertSee('&copy; 2026 Sygrad. ' . __('messages.all_rights_reserved'), false);
    }

    /**
     * Test that ListShops, ListPiggyBanks, and ListTransactions contain the ExportAction in their header actions.
     */
    public function test_list_pages_have_explicit_export_header_actions(): void
    {
        // 1. Verify ListShops has pxlrbt ExportAction
        $listShops = new ListShops();
        $reflectorShops = new \ReflectionMethod(ListShops::class, 'getHeaderActions');
        $reflectorShops->setAccessible(true);
        $shopsActions = $reflectorShops->invoke($listShops);

        $hasShopExport = false;
        foreach ($shopsActions as $action) {
            if ($action instanceof \pxlrbt\FilamentExcel\Actions\ExportAction) {
                $hasShopExport = true;
                $this->assertEquals('Dışa Aktar', $action->getLabel());
                break;
            }
        }
        $this->assertTrue($hasShopExport, 'ListShops is missing explicit pxlrbt ExportAction.');

        // 2. Verify ListPiggyBanks has pxlrbt ExportAction
        $listPiggy = new ListPiggyBanks();
        $reflectorPiggy = new \ReflectionMethod(ListPiggyBanks::class, 'getHeaderActions');
        $reflectorPiggy->setAccessible(true);
        $piggyActions = $reflectorPiggy->invoke($listPiggy);

        $hasPiggyExport = false;
        foreach ($piggyActions as $action) {
            if ($action instanceof \pxlrbt\FilamentExcel\Actions\ExportAction) {
                $hasPiggyExport = true;
                $this->assertEquals('Dışa Aktar', $action->getLabel());
                break;
            }
        }
        $this->assertTrue($hasPiggyExport, 'ListPiggyBanks is missing explicit pxlrbt ExportAction.');

        // 3. Verify ListTransactions has pxlrbt ExportAction
        $listTrans = new ListTransactions();
        $reflectorTrans = new \ReflectionMethod(ListTransactions::class, 'getHeaderActions');
        $reflectorTrans->setAccessible(true);
        $transActions = $reflectorTrans->invoke($listTrans);

        $hasTransExport = false;
        foreach ($transActions as $action) {
            if ($action instanceof \pxlrbt\FilamentExcel\Actions\ExportAction) {
                $hasTransExport = true;
                $this->assertEquals('Dışa Aktar', $action->getLabel());
                break;
            }
        }
        $this->assertTrue($hasTransExport, 'ListTransactions is missing explicit pxlrbt ExportAction.');
    }
}
