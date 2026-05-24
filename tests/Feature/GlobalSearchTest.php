<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\PiggyBank;
use App\Filament\Resources\Shops\ShopResource;
use App\Filament\Resources\PiggyBanks\PiggyBankResource;
use App\Filament\Resources\Users\UserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test global search configuration for UserResource.
     */
    public function test_user_resource_global_search_config(): void
    {
        // 1. Assert title attribute is name
        $this->assertEquals('name', UserResource::getRecordTitleAttribute());

        // 2. Assert searchable attributes
        $searchable = UserResource::getGloballySearchableAttributes();
        $this->assertContains('name', $searchable);
        $this->assertContains('email', $searchable);

        // 3. Assert search result details content
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);

        $details = UserResource::getGlobalSearchResultDetails($user);
        $this->assertArrayHasKey(__('system.email'), $details);
        $this->assertArrayHasKey(__('system.role'), $details);
        $this->assertEquals('john@example.com', $details[__('system.email')]);
        $this->assertEquals(__('system.field_agent'), $details[__('system.role')]);
    }

    /**
     * Test global search configuration for ShopResource.
     */
    public function test_shop_resource_global_search_config(): void
    {
        // 1. Assert title attribute is name
        $this->assertEquals('name', ShopResource::getRecordTitleAttribute());

        // 2. Assert searchable attributes
        $searchable = ShopResource::getGloballySearchableAttributes();
        $this->assertContains('name', $searchable);
        $this->assertContains('city', $searchable);
        $this->assertContains('district', $searchable);

        // 3. Assert search result details content
        $shop = Shop::create([
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'name' => 'Kardelen Market',
            'is_active' => true,
        ]);

        $details = ShopResource::getGlobalSearchResultDetails($shop);
        $this->assertArrayHasKey(__('system.city'), $details);
        $this->assertArrayHasKey(__('system.district'), $details);
        $this->assertEquals('Istanbul', $details[__('system.city')]);
        $this->assertEquals('Kadikoy', $details[__('system.district')]);
    }

    /**
     * Test global search configuration for PiggyBankResource.
     */
    public function test_piggy_bank_resource_global_search_config(): void
    {
        // 1. Assert title attribute is unique_box_id
        $this->assertEquals('unique_box_id', PiggyBankResource::getRecordTitleAttribute());

        // 2. Assert searchable attributes
        $searchable = PiggyBankResource::getGloballySearchableAttributes();
        $this->assertContains('unique_box_id', $searchable);
        $this->assertContains('name', $searchable);

        // 3. Assert search result details content
        $shop = Shop::create([
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'name' => 'Kardelen Market',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'field_agent',
        ]);

        $piggyBank = PiggyBank::create([
            'unique_box_id' => 'KMB-101',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => $user->id,
            'name' => 'Front Desk Box',
            'current_balance' => 450.50,
        ]);

        $details = PiggyBankResource::getGlobalSearchResultDetails($piggyBank);
        $this->assertArrayHasKey(__('system.shop'), $details);
        $this->assertArrayHasKey(__('system.current_balance'), $details);
        $this->assertEquals('Kardelen Market', $details[__('system.shop')]);
        $this->assertEquals('450,50 TL', $details[__('system.current_balance')]);
    }
}
