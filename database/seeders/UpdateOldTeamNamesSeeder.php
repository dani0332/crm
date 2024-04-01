<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateOldTeamNamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the old names and corresponding new names
        $nameMappings = [
            'RM-NB' => 'Best',
            'RM-Speed' => 'Good',
            'EBP' => 'Entry-Level',
        ];

        $isTeams = DB::table('teams')->whereIn('name', array_keys($nameMappings))->get()->count();
        if ($isTeams > 0) {
            // Update the team names
            foreach ($nameMappings as $oldName => $newName) {
                DB::table('teams')->where('name', $oldName)->update(['name' => $newName]);
            }
        }

        $isHealthQuoteTeams = DB::table('health_quote_request')
            ->whereIn('health_team_type', array_keys($nameMappings))
            ->get()->count();

        if ($isHealthQuoteTeams > 0) {
            // Update health_quote_request table
            DB::table('health_quote_request')
                ->whereIn('health_team_type', array_keys($nameMappings))
                ->update(['health_team_type' => DB::raw('CASE 
                                               WHEN health_team_type = "EBP" THEN "Entry-Level"
                                               WHEN health_team_type = "RM-Speed" THEN "Good"
                                               WHEN health_team_type = "RM-NB" THEN "Best"
                                             END')]);
        }
    }

}
