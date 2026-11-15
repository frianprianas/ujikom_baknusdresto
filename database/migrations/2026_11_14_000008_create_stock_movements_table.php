<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['in', 'out', 'adjustment'])->default('in'); // in = restock, out = sold, adjustment = stock opname
            $table->integer('quantity');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->decimal('unit_cost', 12, 2)->default(0); // Harga beli/modal per item
            $table->decimal('total_cost', 12, 2)->default(0); // Total modal pengeluaran
            $table->string('reference_no', 100)->nullable(); // No nota/faktur restock atau invoice kasir
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
