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
            $table->integer('qty')->default(0);
            $table->text('revised_specs', 2000)->nullable();
            $table->string('status')->default('Pending'); //values: Pending|Partial|Complete
            $table->unsignedBigInteger('pr_id')->nullable();
            $table->foreign('pr_id')->references('id')->on('pr_transactions');
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
