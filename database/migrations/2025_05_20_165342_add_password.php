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
        Schema::table('shareex_credentials', function (Blueprint $table) {
            $table->string('password')->nullable();
        });

        \App\Models\ShareexCredential::query()
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
        Schema::table('shareex_credentials', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
