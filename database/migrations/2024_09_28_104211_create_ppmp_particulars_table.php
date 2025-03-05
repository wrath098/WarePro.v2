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
        Schema::create('ppmp_particulars', function (Blueprint $table) {
            $table->id();
            $table->integer('qty_first')->default(0);
            $table->integer('qty_second')->default(0);
            $table->integer('adjusted_firstQty')->default(0)->comment('For Adjustment if didnt met the proposed budget');
            $table->integer('adjusted_secondQty')->default(0)->comment('For Adjustment if didnt met the proposed budget');
            $table->integer('tresh_first_qty')->default(0)->comment('Basis for Qty Issuances');
            $table->integer('tresh_adjustment')->default(0)->comment('Basis for Qty Issuances');
            $table->integer('released_qty')->default(0)->comment('For Product Requisition monitoring of released qty');
            $table->unsignedBigInteger('prod_id')->nullable();
            $table->unsignedBigInteger('price_id')->nullable();
            $table->unsignedBigInteger('trans_id')->nullable();
            $table->foreign('prod_id')->references('id')->on('products');
            $table->foreign('price_id')->references('id')->on('product_prices');
            $table->foreign('trans_id')->references('id')->on('ppmp_transactions');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppmp_particulars');
    }
};
