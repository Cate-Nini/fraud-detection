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
    $table->id();
    $table->foreignId('account_id')->references('id')->on('transactions');
    $table->enum('type', ['deposit', 'withdrawal', 'transfer']);
    $table->decimal('amount', 15, 2);
    $table->foreignId('status_id')->references('id')->on('status');
    $table->text('description')->nullable();
    $table->timestamp('date')->useCurrent();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
