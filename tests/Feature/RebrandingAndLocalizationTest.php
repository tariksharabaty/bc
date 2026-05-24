<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\PiggyBank;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RebrandingAndLocalizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that global configuration files successfully resolve to the new brand name.
     */
    public function test_app_name_is_globally_rebranded(): void
    {
        $this->assertEquals('Kumbara Takip Sistemi', config('app.name'));
    }

    /**
     * Test that the Filament Panel Providers use the updated brandName.
     */
    public function test_filament_panels_use_rebranded_name(): void
    {
        $adminPanel = Filament::getPanel('admin');
        $sahaPanel = Filament::getPanel('saha');

        $this->assertEquals('Kumbara Takip Sistemi', $adminPanel->getBrandName());
        $this->assertEquals('Kumbara Takip Sistemi', $sahaPanel->getBrandName());
    }

    /**
     * Test that Filament panels support dynamic RTL direction when locale is 'ar'.
     */
    public function test_filament_panels_support_rtl_dynamically_for_arabic(): void
    {
        // Force app locale to 'ar' (RTL)
        app()->setLocale('ar');

        $response = $this->get('/admin/login');
        $response->assertSuccessful();
        
        // Assert that the HTML contains dir="rtl" direction tag
        $response->assertSee('dir="rtl"', false);

        // Reset locale back to default
        app()->setLocale('tr');
    }

    /**
     * Test that the Language Switcher is strictly configured for ['tr', 'en', 'ar'].
     */
    public function test_language_switcher_is_strictly_configured_for_three_locales(): void
    {
        // Resolve the switcher instance
        $switch = LanguageSwitch::make();

        $this->assertEquals(['tr', 'en', 'ar'], $switch->getLocales());
    }

    /**
     * Test that the custom footer displays the rebranded application name.
     */
    public function test_custom_footer_displays_rebranded_name(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
        $this->actingAs($admin);

        $response = $this->followingRedirects()->get('/admin');
        $response->assertSee('Kumbara Takip Sistemi');
        $response->assertSee('Sygrad');
        $response->assertSee(__('messages.all_rights_reserved'));
        $response->assertDontSee('Sadaka Taşı');
    }

    /**
     * Test that the login page displays the rebranded application name.
     */
    public function test_login_page_displays_rebranded_name(): void
    {
        $response = $this->get('/admin/login');
        $response->assertSuccessful();
        $response->assertSee('Kumbara Takip Sistemi');
        $response->assertSee('Hoş Geldiniz');
        $response->assertDontSee('Sadaka Taşı');
        $response->assertSee('Sygrad');
    }

    /**
     * Test that the QR print view displays the rebranded application name.
     */
    public function test_qr_print_view_displays_rebranded_name(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
        $this->actingAs($admin);

        $shop = Shop::create([
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'name' => 'Test Market',
            'is_active' => true,
        ]);

        $piggy = PiggyBank::create([
            'unique_box_id' => 'KMB-TEST',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => null,
            'name' => 'Box Name',
            'current_balance' => 0.0,
        ]);

        // Visit the print view
        $response = $this->get('/piggy-banks/' . $piggy->id . '/qr-print');
        $response->assertSuccessful();
        $response->assertSee('Kumbara Takip Sistemi');
        $response->assertSee('Kumbara QR Kodu');
        $response->assertDontSee('Sadaka Taşı');
    }
}
