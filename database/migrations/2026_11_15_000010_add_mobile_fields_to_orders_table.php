<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_phone', 30)->nullable()->after('customer_name');
            $table->text('delivery_address')->nullable()->after('customer_phone');
            $table->string('delivery_status', 30)->nullable()->default('pending')->after('status');
            $table->boolean('is_paid')->default(false)->after('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_phone', 'delivery_address', 'delivery_status', 'is_paid']);
        });
    }
};
