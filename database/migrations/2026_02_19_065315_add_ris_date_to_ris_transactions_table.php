<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ris_transactions', function (Blueprint $table) {
            $table->date('ris_date')->nullable()->after('ris_no');
        });
    }

    public function down(): void
    {
        Schema::table('ris_transactions', function (Blueprint $table) {
            $table->dropColumn('ris_date');
        });
    }
};
