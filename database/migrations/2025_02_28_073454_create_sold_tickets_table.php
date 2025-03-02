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
        Schema::create('sold_tickets', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('invoiceId')->unique();
            $table->bigInteger('event_id'); // Remove unique()
            $table->decimal('cost', 10, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('profit', 10, 2);
            $table->decimal('roi', 10, 2);
            $table->foreign('event_id')->references('event_id')->on('inventories')->onDelete('cascade');
            $table->datetime('invoiceDate')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sold_tickets');
    }
};
