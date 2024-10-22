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
        Schema::create('capital_outlays', function (Blueprint $table) {
            $table->id();
            $table->string('year')->nullable();
            $table->string('cluster')->nullable();
            $table->double('amount')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('fund_id')->nullable();
            $table->foreign('fund_id')->references('id')->on('funds');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_outlays');
    }
};
