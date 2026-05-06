<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
           DB::table('users')->insert([
        'role_id'    => 1, // Admin
        'status_id'  => 1, // Active (user category)
        'name'       => 'ABC Admin',
        'email'      => 'admin@abcmicrofinance.com',
        'password'   => Hash::make('Admin@1234'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    }
}
