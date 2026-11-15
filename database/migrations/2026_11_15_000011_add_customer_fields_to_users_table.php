<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->unique()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('address_benchmark', 255)->nullable()->after('address'); // Patokan jalan
            $table->string('otp_code', 10)->nullable()->after('address_benchmark');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NULL");
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'cashier', 'waiter', 'kitchen', 'customer') DEFAULT 'customer'");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address', 'address_benchmark', 'otp_code', 'otp_expires_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'cashier', 'waiter', 'kitchen') DEFAULT 'cashier'");
        }
    }
};
