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
        Schema::create('product_inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->bigInteger('qty')->nullable();
            $table->bigInteger('stock_qty')->nullable()->comment('if qty == stock_qty, return complete');
            $table->text('notes')->nullable()->comment('Comments or Other Info');
            $table->date('date_expiry')->nullable();
            $table->string('dispatch')->default('incomplete')->comment('Dedicated for expiry monitoring');
            $table->bigInteger('current_stock')->nullable()->comment('Pre-Current Stock');
            $table->unsignedBigInteger('prodInv_id')->nullable();
            $table->unsignedBigInteger('ref_no')->nullable();
            $table->unsignedBigInteger('prod_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('prodInv_id')->references('id')->on('product_inventories');
            $table->foreign('prod_id')->references('id')->on('products');
            $table->foreign('ref_no')->references('id')->on('iar_particulars');
            $table->foreign('created_by')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_inventory_transactions');
    }
};
