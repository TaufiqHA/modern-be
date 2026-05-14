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
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('address_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 12, 2);
            $table->string('type');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->string('tracking_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
