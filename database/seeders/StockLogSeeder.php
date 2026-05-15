<?php

namespace Database\Seeders;

use App\Models\StockLog;
use Illuminate\Database\Seeder;

class StockLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StockLog::factory()->count(20)->create();
    }
}
