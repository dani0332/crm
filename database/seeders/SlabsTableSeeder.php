<?php

namespace Database\Seeders;

use App\Models\Slab;
use Illuminate\Database\Seeder;

class SlabsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $slabs = Slab::all();

        if (count($slabs) > 0) {
            return;
        }

        Slab::create([
            'title' => 'Slab 1',
        ]);
        Slab::create([
            'title' => 'Slab 2',
        ]);
        Slab::create([
            'title' => 'Slab 3',
        ]);
        Slab::create([
            'title' => 'Slab 4',
        ]);
    }
}
