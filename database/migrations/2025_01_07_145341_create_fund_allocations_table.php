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
        Schema::create('fund_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->string('semester')->nullable();
            $table->double('amount')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('cap_id')->nullable();
            $table->foreign('cap_id')->references('id')->on('capital_outlays');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_allocations');
    }
};
