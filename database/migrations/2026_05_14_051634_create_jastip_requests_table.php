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
        Schema::create('jastip_requests', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('product_name');
            $table->string('product_link');
            $table->string('image_url')->nullable();
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('quote', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jastip_requests');
    }
};
