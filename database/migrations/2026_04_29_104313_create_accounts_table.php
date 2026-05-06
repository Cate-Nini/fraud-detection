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
         Schema::create('accounts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');  // ← column must be here first
        $table->unsignedBigInteger('status_id'); // ← column must be here first
        $table->string('account_number')->unique();
        $table->decimal('balance', 15, 2)->default(0.00);
        $table->timestamps();

        // FK declarations come AFTER the columns
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('status_id')->references('id')->on('status');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
