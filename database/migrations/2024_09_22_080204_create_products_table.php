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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('prod_newNo', 10)->nullable();
            $table->text('prod_desc')->nullable();
            $table->string('prod_unit', 50)->nullable();
            $table->string('prod_status', 20)->default('active');// active || deactivate
            $table->string('prod_remarks', 20)->nullable();
            $table->string('prod_oldNo', 10)->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('item_id')->references('id')->on('item_classes');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();

            //$table->string('prod_type', 20)->default('modify'); // Modify || Fixed Quantity
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
