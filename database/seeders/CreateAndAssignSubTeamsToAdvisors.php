<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class CreateAndAssignSubTeamsToAdvisors extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = $this->getMappingData();

        foreach ($data as $team) {
            $parentTeam = Team::where('name', '=', $team['name'])->first();

            if (! $parentTeam) {
                $parentTeam = Team::create([
                    'name' => $team['name'],
                    'type' => 2, // team
                    'is_active' => true,
                    'parent_team_id' => 2, // Car
                ]);
            }

            foreach ($team['subTeams'] as $subTeam) {
                $subTeamObj = Team::where(['name' => $subTeam['name'], 'parent_team_id' => $parentTeam->id])->first();

                if (! $subTeamObj) {
                    $subTeamObj = Team::create([
                        'name' => $subTeam['name'],
                        'type' => 3, // sub team
                        'is_active' => true,
                        'parent_team_id' => $parentTeam->id,
                    ]);
                }

                $this->assignSubTeamToUsers($subTeam['advisors'], $subTeamObj);
            }
        }
    }

    private function getMappingData()
    {
        return [
            [
                'name' => 'Organic',
                'type' => 2, // team
                'subTeams' => [
                    [
                        'name' => 'Value',
                        'type' => 3,
                        'advisors' => [
                            'sylvester.joseph@insurancemarket.ae',
                            'shoeb.khan@insurancemarket.ae',
                            'muddasir.ali@insurancemarket.ae',
                            'harjeet.singh@insurancemarket.ae',
                            'anoop.nair@insurancemarket.ae',
                            'adrian.mercado@insurancemarket.ae',
                            'pooja.gangadhar@insurancemarket.ae',
                        ],
                    ],
                    [
                        'name' => 'Volume',
                        'type' => 3,
                        'advisors' => [
                            'vislavath.praveen@insurancemarket.ae',
                            'nandini.pentakota@insurancemarket.ae',
                            'muhammad.faisal@insurancemarket.ae',
                            'mona.zuhaib@insurancemarket.ae',
                            'kunal.chatterjee@insurancemarket.ae',
                            'kritika.sarup@insurancemarket.ae',
                            'jill.lim@insurancemarket.ae',
                            'jayawardhan.kadambi@insurancemarket.ae',
                            'james.mondal@insurancemarket.ae',
                            'faizan.siddiqui@insurancemarket.ae',
                            'blessie.aberasturi@insurancemarket.ae',
                            'ashish.pal@insurancemarket.ae',
                            'aman.ali@insurancemarket.ae',
                            'ajmal.khan@insurancemarket.ae',
                            'adeel.farooq@insurancemarket.ae',
                            'abdul.moiz@insurancemarket.ae',
                            'shaukat.alam@insurancemarket.ae',
                            'nitesh.mehar@insurancemarket.ae',
                            'mohd.itoo@insurancemarket.ae',
                            'satyajeet.girdhar@insurancemarket.ae',
                            'roja.naidu@insurancemarket.ae',
                            'saquib.musharraf@insurancemarket.ae',
                            'sarvjeet.singh@insurancemarket.ae',
                            'sameer.ahmed@insurancemarket.ae',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Motor Corporate',
                'type' => 2, // team
                'subTeams' => [
                    [
                        'name' => 'Ecom Leads',
                        'type' => 3,
                        'advisors' => ['vijay.ragav@insurancemarket.ae', 'anoop.shekhar@insurancemarket.ae'],
                    ],
                    [
                        'name' => 'NB Commercial',
                        'type' => 3,
                        'advisors' => ['hemal.mehta@insurancemarket.ae', 'hoor.javed@insurancemarket.ae'],
                    ],
                    [
                        'name' => 'MC Renewals',
                        'type' => 3,
                        'advisors' => ['ganesh.nadarajan@insurancemarket.ae'],
                    ],
                ],
            ],
        ];
    }

    private function assignSubTeamToUsers($users, $subTeam)
    {
        foreach ($users as $userEmail) {
            $user = User::where('email', $userEmail)->first();
            if ($user && ! isset($user->sub_team_id)) {
                $user->sub_team_id = $subTeam['id'];
                $user->save();
            }
        }
    }
}
