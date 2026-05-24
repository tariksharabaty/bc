<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('piggy_banks', function (Blueprint $table) {
            if (!Schema::hasColumn('piggy_banks', 'donation_category')) {
                $table->string('donation_category')->default('money')->after('current_balance');
            } else {
                $table->string('donation_category')->default('money')->change();
            }

            if (!Schema::hasColumn('piggy_banks', 'category_details')) {
                $table->json('category_details')->nullable()->after('donation_category');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'donation_category')) {
                $table->string('donation_category')->default('money')->after('amount');
            } else {
                $table->string('donation_category')->default('money')->change();
            }

            if (!Schema::hasColumn('transactions', 'category_details')) {
                $table->json('category_details')->nullable()->after('donation_category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('piggy_banks', function (Blueprint $table) {
            $table->dropColumn('category_details');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('category_details');
        });
    }
};
