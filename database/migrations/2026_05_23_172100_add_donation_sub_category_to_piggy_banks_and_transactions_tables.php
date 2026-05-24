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
            $table->string('donation_sub_category')->nullable()->after('donation_category');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('donation_sub_category')->nullable()->after('donation_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('piggy_banks', function (Blueprint $table) {
            $table->dropColumn('donation_sub_category');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('donation_sub_category');
        });
    }
};
