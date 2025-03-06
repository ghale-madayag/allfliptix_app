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
        Schema::create('sold_inventory_totals', function (Blueprint $table) {
            $table->id();
            $table->string('period'); // Store period type: 7d, 30d, 90d, 365d
            $table->dateTime('invoice_date_from');
            $table->dateTime('invoice_date_to');
            $table->decimal('total_profit', 10, 2)->default(0);
            $table->decimal('total_profit_margin', 8, 2)->default(0);
            $table->integer('total_qty')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sold_inventory_totals');
    }
};
