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
        Schema::table('ppmp_transactions', function (Blueprint $table) {
            $table->string('baseline_adjustment_type')
                ->default('grouped')
                ->after('price_adjustment')
                ->comment('Allowed values: grouped, custom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppmp_transactions', function (Blueprint $table) {
            $table->dropColumn('baseline_adjustment_type');
        });
    }
};
