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
        Schema::create('pr_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('pr_no')->nullable();
            $table->string('semester')->nullable();
            $table->decimal('qty_adjustment', 5, 2)->default(1.00);
            $table->string('pr_desc')->nullable();
            $table->string('pr_status')->default('draft');
            $table->unsignedBigInteger('trans_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('trans_id')->references('id')->on('ppmp_transactions');
            $table->foreign('created_by')->references('id')->on('users');
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
        Schema::dropIfExists('pr_transactions');
    }
};
