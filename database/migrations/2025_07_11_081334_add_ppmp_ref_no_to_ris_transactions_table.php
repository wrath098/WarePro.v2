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
        Schema::table('ris_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('ppmp_ref_no')->nullable()->after('office_id');
            $table->foreign('ppmp_ref_no')->references('id')->on('ppmp_particulars');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ris_transactions', function (Blueprint $table) {
            $table->dropForeign(['ppmp_ref_no']);
            $table->dropColumn('ppmp_ref_no'); 
        });
    }
};
