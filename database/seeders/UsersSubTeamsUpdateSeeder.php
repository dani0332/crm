<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSubTeamsUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $users = User::select('id', 'team_id', 'sub_team_id')->whereNotNull('sub_team_id')->get();

        foreach ($users as $user) {
            $is_team = DB::table('user_team')
                ->where(['user_id', $user->id, 'team_id', $user->sub_team_id])->first();
            if (empty($is_team)) {
                if (! empty($user->sub_team_id)) {
                    DB::table('user_team')->create([
                        'user_id' => $user->id,
                        'team_id' => $user->sub_team_id,
                    ]);
                }
            }
        }

    }
}
