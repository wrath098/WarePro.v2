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
        Schema::create('ppmp_consolidateds', function (Blueprint $table) {
            $table->id();
            $table->integer('qty_first')->default(0);
            $table->integer('qty_second')->default(0);
            $table->unsignedBigInteger('prod_id')->nullable();
            $table->unsignedBigInteger('price_id')->nullable();
            $table->unsignedBigInteger('trans_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('prod_id')->references('id')->on('products');
            $table->foreign('price_id')->references('id')->on('product_prices');
            $table->foreign('trans_id')->references('id')->on('ppmp_transactions');
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
        Schema::dropIfExists('ppmp_consolidateds');
    }
};
