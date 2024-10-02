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
        Schema::create('product_ppmp_exceptions', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->nullable();
            $table->unsignedBigInteger('prod_id')->nullable();
            $table->text('status')->default('active');
            $table->foreign('prod_id')->references('id')->on('products');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ppmp_exceptions');
    }
};
