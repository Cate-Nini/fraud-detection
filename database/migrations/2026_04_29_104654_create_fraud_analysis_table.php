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
       Schema::create('fraud_analysis', function (Blueprint $table) {
    $table->id();
    $table->foreignId('transaction_id')->references('id')->on ('transactions');
    $table->enum('prediction', ['Normal', 'Suspicious']);
    $table->float('anomaly_score');      // Isolation Forest score
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_analysis');
    }
};
