<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();   // starter | pro | enterprise
            $table->string('name');
            $table->unsignedInteger('price_cents')->default(0);
            $table->string('interval', 10)->default('monthly'); // monthly | yearly
            $table->string('currency', 3)->default('USD');
            $table->json('limits')->nullable();      // { sku_max, orders_per_month, staff_max, storage_mb }
            $table->boolean('is_active')->default(true)->index();
            $table->string('paystack_plan_code', 100)->nullable();
            $table->unsignedTinyInteger('trial_days')->default(14);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
