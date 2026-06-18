<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NicheSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('niches')->insert([
            'name' => 'Outro',
            'slug' => 'outro',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
