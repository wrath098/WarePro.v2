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
        Schema::create('ppmp_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('ppmp_code', 20)->nullable();
            $table->string('ppmp_type');
            $table->decimal('price_adjustment', 5, 2)->nullable();
            $table->decimal('qty_adjustment', 5, 2)->nullable();
            $table->string('ppmp_status', 20)->default('draft');
            $table->string('ppmp_remarks', 10)->nullable();
            $table->unsignedBigInteger('office_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('office_id')->references('id')->on('offices');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppmp_transactions');
    }
};
