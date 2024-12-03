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
        Schema::create('product_inventories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('qtyOnStock')->nullable();
            $table->string('location')->nullable();
            $table->bigInteger('reorderLevel')->nullable();
            $table->unsignedBigInteger('prod_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('prod_id')->references('id')->on('products');
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
        Schema::dropIfExists('product_inventories');
    }
};
