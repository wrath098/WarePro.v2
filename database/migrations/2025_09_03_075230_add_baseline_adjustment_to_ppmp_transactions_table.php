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
            $table->string('office_ppmp_ids')
                ->nullable()
                ->after('description')
                ->comment('Consolidated Office PPMP');
            $table->string('baseline_adjustment_type')
                ->default('grouped')
                ->after('office_ppmp_ids')
                ->comment('Allowed values: grouped, custom');
            $table->string('init_qty_adjustment')
                ->nullable()
                ->after('baseline_adjustment_type')
                ->comment('Initial Quantity Adjustment');;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppmp_transactions', function (Blueprint $table) {
            $table->dropColumn('office_ppmp_ids');
            $table->dropColumn('baseline_adjustment_type');
            $table->dropColumn('init_qty_adjustment');
        });
    }
};
