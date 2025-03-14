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
        Schema::create('archive_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prod_id')->nullable();
            $table->bigInteger('qty')->default(0);
            $table->string('year')->nullable();
            $table->foreign('prod_id')->references('id')->on('products');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_inventories');
    }
};
