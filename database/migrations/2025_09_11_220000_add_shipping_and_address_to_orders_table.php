<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipping_service')) {
                $table->string('shipping_service')->nullable();
            }
            if (!Schema::hasColumn('orders', 'shipping_price')) {
                $table->decimal('shipping_price', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'shipping_deadline')) {
                $table->string('shipping_deadline')->nullable();
            }
            if (!Schema::hasColumn('orders', 'cep')) {
                $table->string('cep')->nullable();
            }
            if (!Schema::hasColumn('orders', 'address')) {
                $table->string('address')->nullable();
            }
            if (!Schema::hasColumn('orders', 'address_number')) {
                $table->string('address_number')->nullable();
            }
            if (!Schema::hasColumn('orders', 'address_complement')) {
                $table->string('address_complement')->nullable();
            }
            if (!Schema::hasColumn('orders', 'city')) {
                $table->string('city')->nullable();
            }
            if (!Schema::hasColumn('orders', 'state')) {
                $table->string('state', 2)->nullable();
            }
            if (!Schema::hasColumn('orders', 'transaction_id')) {
                $table->string('transaction_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_service', 'shipping_price', 'shipping_deadline',
                'cep', 'address', 'address_number', 'address_complement', 'city', 'state',
                'transaction_id', 'payment_method'
            ]);
        });
    }
};
