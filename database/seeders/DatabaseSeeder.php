<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Delete existing users with these emails to avoid duplication
        User::whereIn('email', ['admin@sygrad.com', 'tariksharabaty@sygrad.com'])->delete();

        // Create Super Admin
        User::create([
            'name' => 'Süper Admin',
            'email' => 'admin@sygrad.com',
            'password' => Hash::make('123456'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Create Field Agent
        User::create([
            'name' => 'Tarik Sharabaty',
            'email' => 'tariksharabaty@sygrad.com',
            'password' => Hash::make('123456'),
            'role' => 'field_agent',
            'is_active' => true,
        ]);

        // Seed Shops, PiggyBanks, and Transactions
        $shop = \App\Models\Shop::create([
            'city' => 'İstanbul',
            'district' => 'Fatih',
            'neighborhood' => 'Fatih Merkez Mah.',
            'name' => 'Fatih Merkez Market',
            'user_id' => 2, // Tarik Sharabaty
            'address' => 'Akşemsettin Mah. Fatih Cad. No: 12',
            'phone' => '0212 555 44 33',
            'is_active' => true,
        ]);

        $piggy = \App\Models\PiggyBank::create([
            'unique_box_id' => 'KMB-1',
            'shop_id' => $shop->id,
            'assigned_to_user_id' => 2, // Tarik Sharabaty
            'name' => 'Market Kasası Kutusu',
            'current_balance' => 350.00,
        ]);

        \App\Models\Transaction::create([
            'user_id' => 2,
            'piggy_bank_id' => $piggy->id,
            'amount' => 150.00,
            'action_type' => 'collection',
        ]);
        
        \App\Models\Transaction::create([
            'user_id' => 2,
            'piggy_bank_id' => $piggy->id,
            'amount' => 200.00,
            'action_type' => 'collection',
        ]);
    }
}
