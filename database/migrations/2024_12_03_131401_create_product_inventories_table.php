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
            $table->bigInteger('qty_on_stock')->default(0);
            $table->bigInteger('qty_physical_count')->default(0);
            $table->bigInteger('qty_purchase')->default(0);
            $table->bigInteger('qty_issued')->default(0);
            $table->string('location')->nullable();
            $table->bigInteger('reorder_level')->default(1);
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
