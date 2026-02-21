<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::create([
            'name' => 'سورتینگ',
            'currency' => 'ریال'
        ]);
        
        Location::create([
            'name' => 'میدان اصفهان',
            'currency' => 'ریال'
        ]);
        
        Location::create([
            'name' => 'میدان تهران',
            'currency' => 'ریال'
        ]);
    }
}
