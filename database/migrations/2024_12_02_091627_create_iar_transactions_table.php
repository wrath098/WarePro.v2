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
        Schema::create('iar_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sdi_iar_id')->nullable();
            $table->string('po_no')->nullable();
            $table->string('supplier')->nullable();
            $table->string('date')->nullable();
            $table->string('status')->default('pending');
            $table->string('remark')->nullable();
            $table->unsignedBigInteger('pr_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('pr_id')->references('id')->on('pr_transactions');
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
        Schema::dropIfExists('iar_transactions');
    }
};
