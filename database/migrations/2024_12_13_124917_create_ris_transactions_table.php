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
        Schema::create('ris_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('ris_no')->nullable();
            $table->bigInteger('qty')->nullable();
            $table->string('unit')->nullable();
            $table->string('remarks')->nullable();
            $table->string('issued_to')->nullable();
            $table->unsignedBigInteger('prod_id')->nullable();
            $table->unsignedBigInteger('office_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('attachment')->nullable();
            $table->foreign('prod_id')->references('id')->on('products');
            $table->foreign('office_id')->references('id')->on('offices');
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
        Schema::dropIfExists('ris_transactions');
    }
};
