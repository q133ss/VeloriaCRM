<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plans')->insert([
            ['name' => 'lite', 'price' => 0],
            ['name' => 'pro', 'price' => 999],
            ['name' => 'elite', 'price' => 2999],
        ]);
    }
}
