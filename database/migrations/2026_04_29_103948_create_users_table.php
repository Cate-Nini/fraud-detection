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

    Schema::create('users', function (Blueprint $table) {
   $table->id(); // creates column named 'id'
$table->unsignedBigInteger('role_id');
$table->unsignedBigInteger('status_id');
$table->string('name');
$table->string('email')->unique();
$table->string('password');
$table->rememberToken();
$table->timestamps();

$table->foreign('role_id')->references('id')->on('roles');   // references 'id'
$table->foreign('status_id')->references('id')->on('status');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
