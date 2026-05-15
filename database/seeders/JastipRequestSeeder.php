<?php

namespace Database\Seeders;

use App\Models\JastipRequest;
use Illuminate\Database\Seeder;

class JastipRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        JastipRequest::factory()->count(5)->create();
    }
}
