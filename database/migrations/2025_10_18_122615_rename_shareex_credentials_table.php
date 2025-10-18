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
        if (Schema::hasTable('shareex_credentials')) {
            Schema::rename('shareex_credentials', 'shipping_partner_credentials');
        }

        Schema::table('shipping_partner_credentials', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_partner_credentials', 'partner')) {
                $table->string('partner')->default('Shareex')->after('api_password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_partner_credentials', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_partner_credentials', 'partner')) {
                $table->dropColumn('partner');
            }
        });

        if (Schema::hasTable('shipping_partner_credentials')) {
            Schema::rename('shipping_partner_credentials', 'shareex_credentials');
        }
    }
};
