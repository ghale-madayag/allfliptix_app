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
        
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('event_id')->unique(); // Ensure uniqueness for upsert
            $table->string('name');
            $table->dateTime('date');
            $table->string('venue')->nullable();
            $table->integer('sold')->default(0);
            $table->integer('qty')->default(0);
            $table->decimal('profit_margin', 10, 2)->default(0);
            $table->text('stubhub_url')->nullable(); // New column
            $table->string('vivid_url')->nullable();   // New column
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
