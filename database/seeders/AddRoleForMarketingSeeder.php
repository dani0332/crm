<?php

namespace Database\Seeders;

use App\Models\LostReasons;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddRoleForMarketingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $marketingOperationsRole = Role::where('name', 'MARKETING_OPERATIONS')->count();
        if ($marketingOperationsRole == 0) {
            $marketingOperations = Role::create([
                'name' => 'MARKETING_OPERATIONS',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $lostReasonsCarSold = LostReasons::where('text', 'Car sold')->first();
        if (! $lostReasonsCarSold) {
            DB::table('lost_reasons')->insert([
                'text' => 'Car sold',
                'text_ar' => 'Car sold',
                'is_active' => 1,
                'is_deleted' => 0,
            ]);
        }

        $lostReasonsUncontactable = LostReasons::where('text', 'Uncontactable')->first();
        if (! $lostReasonsCarSold) {
            DB::table('lost_reasons')->insert([
                'text' => 'Uncontactable',
                'text_ar' => 'Uncontactable',
                'is_active' => 1,
                'is_deleted' => 0,
            ]);
        }

        $lostReasonsCancelled = LostReasons::where('text', 'Cancelled')->first();
        if (! $lostReasonsCancelled) {
            DB::table('lost_reasons')->insert([
                'text' => 'Cancelled',
                'text_ar' => 'Cancelled',
                'is_active' => 1,
                'is_deleted' => 0,
            ]);
        }
    }
}
