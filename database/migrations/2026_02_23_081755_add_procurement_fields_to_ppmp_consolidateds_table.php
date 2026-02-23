<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ppmp_consolidateds', function (Blueprint $table) {
            $table->string('procurement_mode')->nullable()->after('id');
            $table->boolean('ppc')->default(0)->after('procurement_mode');
            $table->string('start_pa', 7)->nullable()->after('ppc'); 
            $table->string('end_pa', 7)->nullable()->after('start_pa'); 
            $table->string('expected_delivery', 7)->nullable()->after('end_pa'); 
            $table->string('estimated_budget')->nullable()->after('expected_delivery');
            $table->string('supporting_doc')->nullable()->after('estimated_budget');
            $table->string('remarks')->nullable()->after('supporting_doc');
        });
    }

    public function down(): void
    {
        Schema::table('ppmp_consolidateds', function (Blueprint $table) {
            $table->dropColumn([
                'procurement_mode',
                'ppc',
                'start_pa',
                'end_pa',
                'expected_delivery',
                'estimated_budget',
                'supporting_doc',
                'remarks',
            ]);
        });
    }
};
