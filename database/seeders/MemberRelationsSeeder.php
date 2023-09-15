<?php

namespace Database\Seeders;

use App\Models\Lookup;
use Illuminate\Database\Seeder;

class MemberRelationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Lookup::updateOrCreate(['key' => 'member-relation', 'code' => 'relHusband'], ['text' => 'Husband']);
        Lookup::updateOrCreate(['key' => 'member-relation', 'code' => 'relWife'], ['text' => 'Wife']);
        Lookup::updateOrCreate(['key' => 'member-relation', 'code' => 'relMother'], ['text' => 'Mother']);
        Lookup::updateOrCreate(['key' => 'member-relation', 'code' => 'relFather'], ['text' => 'Father']);
        Lookup::updateOrCreate(['key' => 'member-relation', 'code' => 'reSon'], ['text' => 'Son']);
        Lookup::updateOrCreate(['key' => 'member-relation', 'code' => 'relDaughter'], ['text' => 'Daughter']);
        Lookup::updateOrCreate(['key' => 'member-relation', 'code' => 'relBrother'], ['text' => 'Brother']);
        Lookup::updateOrCreate(['key' => 'member-relation', 'code' => 'relSister'], ['text' => 'Sister']);
        Lookup::updateOrCreate(['key' => 'member-relation', 'code' => 'relFriend'], ['text' => 'Friend']);
        Lookup::updateOrCreate(['key' => 'member-relation', 'code' => 'relBusinessPartner'], ['text' => 'Business Partner']);
    }
}
