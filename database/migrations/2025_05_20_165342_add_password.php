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
        if (Schema::hasColumn('shipping_partner_credentials', 'password')) {
            return; // Skip if the column already exists
        }
        Schema::table('shipping_partner_credentials', function (Blueprint $table) {
            $table->string('password')->nullable();
        });

        \App\Models\ShippingPartnerCredential::query()
            ->whereNotNull('api_password')->each(function ($credential) {
                $credential->password = $credential->api_password;
                $credential->save();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_partner_credentials', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
