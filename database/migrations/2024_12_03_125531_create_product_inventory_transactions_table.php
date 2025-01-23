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
            $table->text('notes')->nullable();
            $table->date('date_expiry')->nullable();
            $table->unsignedBigInteger('ref_no')->nullable();
            $table->unsignedBigInteger('prod_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
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
