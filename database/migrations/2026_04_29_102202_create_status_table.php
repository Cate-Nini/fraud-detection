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
     Schema::create('status', function (Blueprint $table) {
    $table->id();
    $table->string('status_name');       // e.g. 'Active', 'Frozen', //  'Closed'
    $table->string('category');       // e.g. 'account', 'transaction', 'user'
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status');
    }
};
