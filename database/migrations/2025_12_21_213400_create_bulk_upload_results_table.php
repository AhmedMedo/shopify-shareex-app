<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bulk_upload_results', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index();
            $table->integer('row_number');
            $table->string('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('area')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending, success, failed
            $table->string('shareex_serial')->nullable();
            $table->text('error_message')->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_upload_results');
    }
};
