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
        Schema::create('iar_particulars', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('item_no')->nullable();
            $table->string('stock_no')->nullable();
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('qty')->nullable();
            $table->decimal('price')->nullable();
            $table->string('status')->default('pending');
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('air_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('air_id')->references('id')->on('iar_transactions');
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
        Schema::dropIfExists('iar_particulars');
    }
};
