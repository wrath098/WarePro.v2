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
            $table->string('account_class_ids')
                ->nullable()
                ->after('description')
                ->comment('Consolidated Account Classess');
            $table->string('office_ppmp_ids')
                ->nullable()
                ->after('account_class_ids')
                ->comment('Consolidated Office PPMP');
            $table->string('init_qty_adjustment')
                ->nullable()
                ->after('office_ppmp_ids')
                ->comment('Initial Quantity Adjustment');
            $table->string('final_qty_adjustment')
                ->nullable()
                ->after('init_qty_adjustment')
                ->comment('Final Quantity Adjustment');;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppmp_transactions', function (Blueprint $table) {
            $table->dropColumn('account_class_ids');
            $table->dropColumn('office_ppmp_ids');
            $table->dropColumn('init_qty_adjustment');
            $table->dropColumn('final_qty_adjustment');
        });
    }
};
