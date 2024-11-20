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
        Schema::create('pr_particulars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prod_id')->nullable();
            $table->decimal('unitPrice', 10, 2)->nullable();
            $table->string('unitMeasure')->nullable();
            $table->integer('qty')->default(0);
            $table->text('revised_specs', 2000)->nullable();
            $table->string('status')->default('draft'); //values: draft|Pending|Partial|Complete
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('pr_id')->nullable();
            $table->unsignedBigInteger('conpar_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('pr_id')->references('id')->on('pr_transactions');
            $table->foreign('conpar_id')->references('id')->on('ppmp_consolidateds');
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
        Schema::dropIfExists('pr_particulars');
    }
};
