<?php

namespace Database\Seeders;

use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;

class addInsuranceProvidersConfiguration extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $insuranceProvidersMapping = [
            ['code' => 'AAIC', 'sage_vendor_id' => 'IP022', 'gl_liaiblity_account' => '55180'],
            ['code' => 'ABNIC', 'sage_vendor_id' => 'IP003', 'gl_liaiblity_account' => '55190'],
            ['code' => 'ADNIC', 'sage_vendor_id' => 'IP019', 'gl_liaiblity_account' => '55150'],
            ['code' => 'ADNT', 'sage_vendor_id' => 'IP020', 'gl_liaiblity_account' => '55160'],
            ['code' => 'AFNIC', 'sage_vendor_id' => 'IP023', 'gl_liaiblity_account' => '55200'],
            ['code' => 'AHAC', 'sage_vendor_id' => 'IP015', 'gl_liaiblity_account' => '55130'],
            ['code' => 'AI', 'sage_vendor_id' => 'IP026', 'gl_liaiblity_account' => '55230'],
            ['code' => 'AIAW', 'sage_vendor_id' => 'IP053', 'gl_liaiblity_account' => '55530'],
            ['code' => 'AICSAL', 'sage_vendor_id' => 'IP026', 'gl_liaiblity_account' => '55230'],
            ['code' => 'AIG', 'sage_vendor_id' => 'IP015', 'gl_liaiblity_account' => '55130'],
            ['code' => 'ALJALIL', 'sage_vendor_id' => 'IP009', 'gl_liaiblity_account' => '55070'],
            ['code' => 'ALNC', 'sage_vendor_id' => 'IP024', 'gl_liaiblity_account' => '55210'],
            ['code' => 'AMAN', 'sage_vendor_id' => 'IP033', 'gl_liaiblity_account' => '55300'],
            ['code' => 'AMJ', 'sage_vendor_id' => 'IP021', 'gl_liaiblity_account' => '55170'],
            ['code' => 'APR', 'sage_vendor_id' => 'IP038', 'gl_liaiblity_account' => '55350'],
            ['code' => 'ASCANA', 'sage_vendor_id' => 'IP027', 'gl_liaiblity_account' => '55240'],
            ['code' => 'ASNIC', 'sage_vendor_id' => 'IP004', 'gl_liaiblity_account' => '55030'],
            ['code' => 'AXA', 'sage_vendor_id' => 'IP010', 'gl_liaiblity_account' => '55060'],
            ['code' => 'BUP', 'sage_vendor_id' => 'IP006', 'gl_liaiblity_account' => '55080'],
            ['code' => 'CIG', 'sage_vendor_id' => 'IP028', 'gl_liaiblity_account' => '55250'],
            ['code' => 'DATPJSC', 'sage_vendor_id' => 'IP029', 'gl_liaiblity_account' => '55260'],
            ['code' => 'DIC', 'sage_vendor_id' => 'IP030', 'gl_liaiblity_account' => '55270'],
            ['code' => 'DICORI', 'sage_vendor_id' => 'IP032', 'gl_liaiblity_account' => '55290'],
            ['code' => 'DICPSC', 'sage_vendor_id' => 'IP031', 'gl_liaiblity_account' => '55280'],
            ['code' => 'DNIRC', 'sage_vendor_id' => 'IP034', 'gl_liaiblity_account' => '55310'],
            ['code' => 'EECIC', 'sage_vendor_id' => 'IP052', 'gl_liaiblity_account' => '55520'],
            ['code' => 'EI', 'sage_vendor_id' => 'IP012', 'gl_liaiblity_account' => '55100'],
            ['code' => 'FID', 'sage_vendor_id' => 'IP035', 'gl_liaiblity_account' => '55320'],
            ['code' => 'FPIL', 'sage_vendor_id' => 'IP036', 'gl_liaiblity_account' => '55330'],
            ['code' => 'IHC', 'sage_vendor_id' => 'IP037', 'gl_liaiblity_account' => '55340'],
            ['code' => 'MAXMED', 'sage_vendor_id' => 'IP039', 'gl_liaiblity_account' => '55360'],
            ['code' => 'MEDGULF', 'sage_vendor_id' => 'IP048', 'gl_liaiblity_account' => '55480'],
            ['code' => 'MOPT', 'sage_vendor_id' => 'IP024', 'gl_liaiblity_account' => '55210'],
            ['code' => 'MTL', 'sage_vendor_id' => 'IP017', 'gl_liaiblity_account' => '55370'],
            ['code' => 'NGI', 'sage_vendor_id' => 'IP007', 'gl_liaiblity_account' => '55050'],
            ['code' => 'NHICD', 'sage_vendor_id' => 'IP040', 'gl_liaiblity_account' => '55380'],
            ['code' => 'NIA', 'sage_vendor_id' => 'IP043', 'gl_liaiblity_account' => '55410'],
            ['code' => 'NIADB', 'sage_vendor_id' => 'IP044', 'gl_liaiblity_account' => '55420'],
            ['code' => 'NLAGICSAOC', 'sage_vendor_id' => 'IP041', 'gl_liaiblity_account' => '55390'],
            ['code' => 'NLGIC', 'sage_vendor_id' => 'IP041', 'gl_liaiblity_account' => '55390'],
            ['code' => 'NOW', 'sage_vendor_id' => 'IP026', 'gl_liaiblity_account' => '55230'],
            ['code' => 'NT', 'sage_vendor_id' => 'IP042', 'gl_liaiblity_account' => '55400'],
            ['code' => 'NTCWATANIA', 'sage_vendor_id' => 'IP042', 'gl_liaiblity_account' => '55400'],
            ['code' => 'NTFPJSC', 'sage_vendor_id' => 'IP045', 'gl_liaiblity_account' => '55430'],
            ['code' => 'NTPJSC', 'sage_vendor_id' => 'IP005', 'gl_liaiblity_account' => '55440'],
            ['code' => 'OALLIANZ', 'sage_vendor_id' => 'IP025', 'gl_liaiblity_account' => '55220'],
            ['code' => 'OI', 'sage_vendor_id' => 'IP049', 'gl_liaiblity_account' => '55490'],
            ['code' => 'OI2', 'sage_vendor_id' => 'IP002', 'gl_liaiblity_account' => '55020'],
            ['code' => 'OIC', 'sage_vendor_id' => 'IP006', 'gl_liaiblity_account' => '55080'],
            ['code' => 'OUNB', 'sage_vendor_id' => 'IP014', 'gl_liaiblity_account' => '55120'],
            ['code' => 'QIC', 'sage_vendor_id' => 'IP001', 'gl_liaiblity_account' => '55010'],
            ['code' => 'RAK', 'sage_vendor_id' => 'IP046', 'gl_liaiblity_account' => '55450'],
            ['code' => 'RSA', 'sage_vendor_id' => 'IP008', 'gl_liaiblity_account' => '55040'],
            ['code' => 'SAICO', 'sage_vendor_id' => 'IP047', 'gl_liaiblity_account' => '55470'],
            ['code' => 'SI', 'sage_vendor_id' => 'IP038', 'gl_liaiblity_account' => '55350'],
            ['code' => 'TE', 'sage_vendor_id' => 'IP013', 'gl_liaiblity_account' => '55110'],
            ['code' => 'TM', 'sage_vendor_id' => 'IP011', 'gl_liaiblity_account' => '55090'],
            ['code' => 'UI', 'sage_vendor_id' => 'IP050', 'gl_liaiblity_account' => '55500'],
            ['code' => 'VIV', 'sage_vendor_id' => 'IP031', 'gl_liaiblity_account' => '55280'],
            ['code' => 'ZILL', 'sage_vendor_id' => 'IP051', 'gl_liaiblity_account' => '55510'],
        ];

        foreach ($insuranceProvidersMapping as $providerMapping) {
            InsuranceProvider::where('code', $providerMapping['code'])
                ->update([
                    'sage_vendor_id' => $providerMapping['sage_vendor_id'],
                    'gl_liaiblity_account' => $providerMapping['gl_liaiblity_account'],
                ]);
        }

    }
}
