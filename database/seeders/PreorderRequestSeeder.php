<?php

namespace Database\Seeders;

use App\Models\PreorderRequest;
use Illuminate\Database\Seeder;

class PreorderRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PreorderRequest::factory()->count(10)->create();
    }
}
