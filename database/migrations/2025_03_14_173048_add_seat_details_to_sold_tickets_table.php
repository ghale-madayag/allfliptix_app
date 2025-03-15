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
        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->string('customerDisplayName')->after('event_id')->nullable();
            $table->integer('lowSeat')->after('customerDisplayName')->nullable();
            $table->integer('highSeat')->after('lowSeat')->nullable();
            $table->string('section')->after('highSeat')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->dropColumn(['customerDisplayName', 'lowSeat', 'highSeat', 'section']);
        });
    }
};
