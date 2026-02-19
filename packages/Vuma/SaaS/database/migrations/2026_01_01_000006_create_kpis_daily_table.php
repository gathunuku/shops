<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kpis_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique()->index();
            $table->unsignedInteger('active_tenants')->default(0);
            $table->unsignedInteger('new_tenants')->default(0);
            $table->unsignedInteger('suspended_tenants')->default(0);
            $table->unsignedBigInteger('gmv_cents')->default(0);
            $table->unsignedBigInteger('mrr_cents')->default(0);
            $table->unsignedInteger('invoices_paid')->default(0);
            $table->unsignedInteger('invoices_failed')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpis_daily');
    }
};
