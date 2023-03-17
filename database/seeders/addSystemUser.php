<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addSystemUser extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $systemUser = User::where('name', 'System User')->count();
        if ($systemUser == 0) {
            $systemUser = User::create([
                'name' => 'System User',
                'password' => '12345',
                'email' => 'system@insurancemarket.ae',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $role = Role::where('name', 'ADMIN')->first();
            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => 'App\\Models\\User',
                'model_id' => $systemUser->id,
            ]);
        }
    }
}
