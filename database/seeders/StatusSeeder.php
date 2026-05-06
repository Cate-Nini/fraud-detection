<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    DB::table('status')->insert([
        // User statuses
        ['status_name' => 'Active',     'category' => 'user',        'created_at' => now(), 'updated_at' => now()],
        ['status_name' => 'Suspended',  'category' => 'user',        'created_at' => now(), 'updated_at' => now()],

        // Account statuses
        ['status_name' => 'Active',     'category' => 'account',     'created_at' => now(), 'updated_at' => now()],
        ['status_name' => 'Frozen',     'category' => 'account',     'created_at' => now(), 'updated_at' => now()],
        ['status_name' => 'Closed',     'category' => 'account',     'created_at' => now(), 'updated_at' => now()],

        // Transaction statuses
        ['status_name' => 'Completed',  'category' => 'transaction', 'created_at' => now(), 'updated_at' => now()],
        ['status_name' => 'Pending',    'category' => 'transaction', 'created_at' => now(), 'updated_at' => now()],
        ['status_name' => 'Flagged',    'category' => 'transaction', 'created_at' => now(), 'updated_at' => now()],
    ]);
}
}
