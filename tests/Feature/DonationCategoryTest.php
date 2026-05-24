<?php

use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Models\PiggyBank;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DonationCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role'     => 'super_admin',
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

    public function test_piggy_bank_and_transaction_category_default_is_money(): void
    {
        $admin = $this->makeAdmin();
        $shop = $this->makeShop();

        $piggy = PiggyBank::create([
            'unique_box_id'       => 'KMB-CAT-1',
            'shop_id'             => $shop->id,
            'assigned_to_user_id' => $admin->id,
            'name'                => 'Money Box',
            'current_balance'     => 100.00,
        ]);

        $this->assertEquals('money', $piggy->donation_category);

        $transaction = Transaction::create([
            'piggy_bank_id' => $piggy->id,
            'user_id'       => $admin->id,
            'action_type'   => 'collection',
            'amount'        => 50.00,
        ]);

        $this->assertEquals('money', $transaction->donation_category);
    }

    public function test_piggy_bank_can_be_assigned_qurbani_category_with_details(): void
    {
        $admin = $this->makeAdmin();
        $shop = $this->makeShop();

        $piggy = PiggyBank::create([
            'unique_box_id'       => 'KMB-CAT-2',
            'shop_id'             => $shop->id,
            'assigned_to_user_id' => $admin->id,
            'name'                => 'Qurbani Box',
            'current_balance'     => 100.00,
            'donation_category'   => 'qurbani',
            'category_details'    => ['qurbani_type' => 'kucukbas'],
        ]);

        $this->assertEquals('qurbani', $piggy->donation_category);
        $this->assertEquals(['qurbani_type' => 'kucukbas'], $piggy->category_details);
    }

    public function test_transaction_form_reactive_donation_category(): void
    {
        $admin = $this->makeAdmin();
        $shop = $this->makeShop();

        $piggy = PiggyBank::create([
            'unique_box_id'       => 'KMB-FOOD-1',
            'shop_id'             => $shop->id,
            'assigned_to_user_id' => $admin->id,
            'name'                => 'Food Box',
            'current_balance'     => 100.00,
            'donation_category'   => 'food',
            'category_details'    => ['food_package_count' => 10],
        ]);

        $this->actingAs($admin);
        filament()->setCurrentPanel(filament()->getPanel('saha'));

        Livewire::actingAs($admin)
            ->test(CreateTransaction::class)
            ->set('data.piggy_bank_id', $piggy->id)
            ->assertFormSet([
                'donation_category' => 'food',
            ])
            ->fillForm([
                'action_type' => 'collection',
                'amount' => 50.00,
                'category_details.food_unit' => 'koli',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('transactions', [
            'piggy_bank_id' => $piggy->id,
            'amount' => 50.00,
            'donation_category' => 'food',
            'category_details' => json_encode([
                'food_package_count' => 10,
                'food_unit' => 'koli',
            ]),
        ]);
    }

    public function test_transaction_form_qurbani_simple_flow(): void
    {
        $admin = $this->makeAdmin();
        $shop = $this->makeShop();

        $piggy = PiggyBank::create([
            'unique_box_id'       => 'KMB-QUR-SIMPLE-1',
            'shop_id'             => $shop->id,
            'assigned_to_user_id' => $admin->id,
            'name'                => 'Qurbani Box',
            'current_balance'     => 100.00,
            'donation_category'   => 'qurbani',
            'category_details'    => ['qurbani_type' => 'buyukbas'],
        ]);

        $this->actingAs($admin);
        filament()->setCurrentPanel(filament()->getPanel('saha'));

        Livewire::actingAs($admin)
            ->test(CreateTransaction::class)
            ->set('data.piggy_bank_id', $piggy->id)
            ->assertFormSet([
                'donation_category' => 'qurbani',
            ])
            ->fillForm([
                'amount' => 2,
                'description' => 'Ahmet Yilmaz ve Ailesi',
                'category_details.qurbani_type' => 'buyukbas_hisse',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('transactions', [
            'piggy_bank_id' => $piggy->id,
            'amount' => 2,
            'description' => 'Ahmet Yilmaz ve Ailesi',
            'donation_category' => 'qurbani',
            'category_details' => json_encode([
                'qurbani_type' => 'buyukbas_hisse',
            ]),
        ]);
    }
}
