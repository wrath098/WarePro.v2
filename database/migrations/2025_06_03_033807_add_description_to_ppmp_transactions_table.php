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
            $table->string('description')->default('Raw File')->after('ppmp_type');
            $table->string('remarks')->nullable()->after('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppmp_transactions', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('remarks');
        });
    }
};
