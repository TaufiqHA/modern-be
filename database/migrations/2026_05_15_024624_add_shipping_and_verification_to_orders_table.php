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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_cost', 12, 2)->default(0)->after('total_amount');
            $table->string('courier_service')->nullable()->after('shipping_cost');
            $table->timestamp('verified_at')->nullable()->after('payment_proof');
            $table->foreignUuid('verified_by')->nullable()->after('verified_at')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['shipping_cost', 'courier_service', 'verified_at', 'verified_by']);
        });
    }
};
