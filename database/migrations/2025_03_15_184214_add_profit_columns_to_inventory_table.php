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
        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('avg_profit_1d', 8, 2)->after('venue')->default(0);
            $table->decimal('avg_profit_3d', 8, 2)->after('avg_profit_1d')->default(0);
            $table->decimal('avg_profit_7d', 8, 2)->after('avg_profit_3d')->default(0);
            $table->decimal('avg_profit_30d', 8, 2)->after('avg_profit_7d')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn([
                'avg_profit_1d',
                'avg_profit_3d',
                'avg_profit_7d',
                'avg_profit_30d',
            ]);
        });
    }
};
