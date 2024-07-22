<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;

class CopyTeamNameToTeamCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = Team::select('id', 'name', 'code')->get();

        foreach ($teams as $team) {
            try {
                if (is_null($team->code) || empty($team->code)) {
                    $code = $team->name;

                    $existingCode = Team::where('code', $code)->first();

                    $suffix = '';
                    $counter = 1;
                    while ($existingCode) {
                        $suffix = '_'.$counter;
                        $existingCode = Team::where('code', $code.$suffix)->first();
                        $counter++;
                    }

                    $team->code = $code.$suffix;
                    $team->save();
                }
            } catch (ModelNotFoundException $e) {
                info('Team with code "'.$code.'" not found during update for team ID: '.$team->id.' and team name: '.$team->name);
            } catch (\Exception $e) {
                info('Error in CopyTeamNameToTeamCodeSeeder against this team id: '.$team->id.' and this team name: '.$team->name.' with message: '.$e->getMessage());
            }
        }
    }
}
