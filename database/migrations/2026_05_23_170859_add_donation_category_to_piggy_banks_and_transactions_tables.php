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
            $table->string('donation_category')->default('general')->after('current_balance');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('donation_category')->default('general')->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('piggy_banks', function (Blueprint $table) {
            $table->dropColumn('donation_category');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('donation_category');
        });
    }
};
