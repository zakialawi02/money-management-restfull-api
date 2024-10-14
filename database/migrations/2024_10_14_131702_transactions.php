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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['income', 'expense']);
            $table->uuid('transactions_category_id')->nullable();
            $table->foreign('transactions_category_id')->references('id')->on('transactions_categories')->nullOnDelete()->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        schema::dropIfExists('transactions');
    }
};
