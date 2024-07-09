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
        /*$insuranceProvidersMapping = [
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
            ['code' => 'STF', 'sage_vendor_id' => 'IP006', 'gl_liaiblity_account' => '55080'],
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
        ];*/

        $insuranceProvidersMapping = [
            ['code' => 'RSA', 'gl_liaiblity_account' => '55040', 'sage_insurer_customer_id' => 'IC008', 'sage_vendor_id' => 'IP008'],
            ['code' => 'AXA', 'gl_liaiblity_account' => '55060', 'sage_insurer_customer_id' => 'IC004', 'sage_vendor_id' => 'IP010'],
            ['code' => 'QIC', 'gl_liaiblity_account' => '55010', 'sage_insurer_customer_id' => 'IC001', 'sage_vendor_id' => 'IP001'],
            ['code' => 'OIC', 'gl_liaiblity_account' => '55080', 'sage_insurer_customer_id' => 'IC006', 'sage_vendor_id' => 'IP006'],
            ['code' => 'TM', 'gl_liaiblity_account' => '55090', 'sage_insurer_customer_id' => 'IC012', 'sage_vendor_id' => 'IP011'],
            ['code' => 'NT', 'gl_liaiblity_account' => '55400', 'sage_insurer_customer_id' => 'IC041', 'sage_vendor_id' => 'IP042'],
            ['code' => 'NIA', 'gl_liaiblity_account' => '55410', 'sage_insurer_customer_id' => 'IC042', 'sage_vendor_id' => 'IP043'],
            ['code' => 'SI', 'gl_liaiblity_account' => '55350', 'sage_insurer_customer_id' => 'IC036', 'sage_vendor_id' => 'IP038'],
            ['code' => 'OI', 'gl_liaiblity_account' => '55490', 'sage_insurer_customer_id' => 'IC048', 'sage_vendor_id' => 'IP049'],
            ['code' => 'NGI', 'gl_liaiblity_account' => '55050', 'sage_insurer_customer_id' => 'IC007', 'sage_vendor_id' => 'IP007'],
            ['code' => 'UI', 'gl_liaiblity_account' => '55500', 'sage_insurer_customer_id' => 'IC049', 'sage_vendor_id' => 'IP050'],
            ['code' => 'ADNIC', 'gl_liaiblity_account' => '55150', 'sage_insurer_customer_id' => 'IC017', 'sage_vendor_id' => 'IP019'],
            ['code' => 'ADNT', 'gl_liaiblity_account' => '55160', 'sage_insurer_customer_id' => 'IC018', 'sage_vendor_id' => 'IP020'],
            ['code' => 'IHC', 'gl_liaiblity_account' => '55340', 'sage_insurer_customer_id' => 'IC035', 'sage_vendor_id' => 'IP037'],
            ['code' => 'ALNC', 'gl_liaiblity_account' => '55210', 'sage_insurer_customer_id' => 'IC023', 'sage_vendor_id' => 'IP024'],
            ['code' => 'OI2', 'gl_liaiblity_account' => '55020', 'sage_insurer_customer_id' => 'IC002', 'sage_vendor_id' => 'IP002'],
            ['code' => 'DNIRC', 'gl_liaiblity_account' => '55310', 'sage_insurer_customer_id' => 'IC032', 'sage_vendor_id' => 'IP034'],
            ['code' => 'CIG', 'gl_liaiblity_account' => '55250', 'sage_insurer_customer_id' => 'IC027', 'sage_vendor_id' => 'IP028'],
            ['code' => 'AIG', 'gl_liaiblity_account' => '55130', 'sage_insurer_customer_id' => 'IC016', 'sage_vendor_id' => 'IP015'],
            ['code' => 'TE', 'gl_liaiblity_account' => '55110', 'sage_insurer_customer_id' => 'IC011', 'sage_vendor_id' => 'IP013'],
            ['code' => 'AMJ', 'gl_liaiblity_account' => '55170', 'sage_insurer_customer_id' => 'IC019', 'sage_vendor_id' => 'IP021'],
            ['code' => 'FID', 'gl_liaiblity_account' => '55320', 'sage_insurer_customer_id' => 'IC033', 'sage_vendor_id' => 'IP035'],
            ['code' => 'DIC', 'gl_liaiblity_account' => '55270', 'sage_insurer_customer_id' => 'IC029', 'sage_vendor_id' => 'IP030'],
            ['code' => 'RAK', 'gl_liaiblity_account' => '55450', 'sage_insurer_customer_id' => 'IC045', 'sage_vendor_id' => 'IP046'],
            ['code' => 'OUNB', 'gl_liaiblity_account' => '55120', 'sage_insurer_customer_id' => 'IC013', 'sage_vendor_id' => 'IP014'],
            ['code' => 'ASCANA', 'gl_liaiblity_account' => '55240', 'sage_insurer_customer_id' => 'IC026', 'sage_vendor_id' => 'IP027'],
            ['code' => 'EI', 'gl_liaiblity_account' => '55100', 'sage_insurer_customer_id' => 'IC015', 'sage_vendor_id' => 'IP012'],
            ['code' => 'NTPJSC', 'gl_liaiblity_account' => '55440', 'sage_insurer_customer_id' => 'IC005', 'sage_vendor_id' => 'IP005'],
            ['code' => 'ASNIC', 'gl_liaiblity_account' => '55030', 'sage_insurer_customer_id' => 'IC003', 'sage_vendor_id' => 'IP004'],
            ['code' => 'MEDGULF', 'gl_liaiblity_account' => '55480', 'sage_insurer_customer_id' => 'IC047', 'sage_vendor_id' => 'IP048'],
            ['code' => 'MTL', 'gl_liaiblity_account' => '55370', 'sage_insurer_customer_id' => 'IC038', 'sage_vendor_id' => 'IP017'],
            ['code' => 'SAICO', 'gl_liaiblity_account' => '55470', 'sage_insurer_customer_id' => 'IC046', 'sage_vendor_id' => 'IP047'],
            ['code' => 'AFNIC', 'gl_liaiblity_account' => '55200', 'sage_insurer_customer_id' => 'IC022', 'sage_vendor_id' => 'IP023'],
            ['code' => 'OALLIANZ', 'gl_liaiblity_account' => '55220', 'sage_insurer_customer_id' => 'IC024', 'sage_vendor_id' => 'IP025'],
            ['code' => 'ALJALIL', 'gl_liaiblity_account' => '55070', 'sage_insurer_customer_id' => 'IC014', 'sage_vendor_id' => 'IP009'],
            ['code' => 'FPIL', 'gl_liaiblity_account' => '55330', 'sage_insurer_customer_id' => 'IC034', 'sage_vendor_id' => 'IP036'],
            ['code' => 'ZILL', 'gl_liaiblity_account' => '55510', 'sage_insurer_customer_id' => 'IC050', 'sage_vendor_id' => 'IP051'],
            ['code' => 'STF', 'gl_liaiblity_account' => '55080', 'sage_insurer_customer_id' => 'IC006', 'sage_vendor_id' => 'IP006'],
            ['code' => 'AHAC', 'gl_liaiblity_account' => '55130', 'sage_insurer_customer_id' => 'IC016', 'sage_vendor_id' => 'IP015'],
            ['code' => 'ST', 'gl_liaiblity_account' => '55080', 'sage_insurer_customer_id' => 'IC006', 'sage_vendor_id' => 'IP006'],
            ['code' => 'AAIC', 'gl_liaiblity_account' => '55180', 'sage_insurer_customer_id' => 'IC020', 'sage_vendor_id' => 'IP022'],
            ['code' => 'ABNIC', 'gl_liaiblity_account' => '55190', 'sage_insurer_customer_id' => 'IC021', 'sage_vendor_id' => 'IP003'],
            ['code' => 'AICSAL', 'gl_liaiblity_account' => '55230', 'sage_insurer_customer_id' => 'IC025', 'sage_vendor_id' => 'IP026'],
            ['code' => 'DATPJSC', 'gl_liaiblity_account' => '55260', 'sage_insurer_customer_id' => 'IC028', 'sage_vendor_id' => 'IP029'],
            ['code' => 'DICPSC', 'gl_liaiblity_account' => '55280', 'sage_insurer_customer_id' => 'IC010', 'sage_vendor_id' => 'IP031'],
            ['code' => 'DICORI', 'gl_liaiblity_account' => '55290', 'sage_insurer_customer_id' => 'IC030', 'sage_vendor_id' => 'IP032'],
            ['code' => 'AMAN', 'gl_liaiblity_account' => '55300', 'sage_insurer_customer_id' => 'IC031', 'sage_vendor_id' => 'IP033'],
            ['code' => 'MAXMED', 'gl_liaiblity_account' => '55360', 'sage_insurer_customer_id' => 'IC037', 'sage_vendor_id' => 'IP039'],
            ['code' => 'NHICD', 'gl_liaiblity_account' => '55380', 'sage_insurer_customer_id' => 'IC039', 'sage_vendor_id' => 'IP040'],
            ['code' => 'NLAGICSAOC', 'gl_liaiblity_account' => '55390', 'sage_insurer_customer_id' => 'IC040', 'sage_vendor_id' => 'IP041'],
            ['code' => 'NTCWATANIA', 'gl_liaiblity_account' => '55400', 'sage_insurer_customer_id' => 'IC041', 'sage_vendor_id' => 'IP042'],
            ['code' => 'NIADB', 'gl_liaiblity_account' => '55420', 'sage_insurer_customer_id' => 'IC043', 'sage_vendor_id' => 'IP044'],
            ['code' => 'NTFPJSC', 'gl_liaiblity_account' => '55430', 'sage_insurer_customer_id' => 'IC044', 'sage_vendor_id' => 'IP045'],
            ['code' => 'EECIC', 'gl_liaiblity_account' => '55520', 'sage_insurer_customer_id' => 'IC051', 'sage_vendor_id' => 'IP052'],
            ['code' => 'APR', 'gl_liaiblity_account' => '55350', 'sage_insurer_customer_id' => 'IC036', 'sage_vendor_id' => 'IP038'],
            ['code' => 'AI', 'gl_liaiblity_account' => '55230', 'sage_insurer_customer_id' => 'IC025', 'sage_vendor_id' => 'IP026'],
            ['code' => 'NOW', 'gl_liaiblity_account' => '55230', 'sage_insurer_customer_id' => 'IC025', 'sage_vendor_id' => 'IP026'],
            ['code' => 'AHAC', 'gl_liaiblity_account' => '55130', 'sage_insurer_customer_id' => 'IC016', 'sage_vendor_id' => 'IP015'],
            ['code' => 'ALJALIL', 'gl_liaiblity_account' => '55070', 'sage_insurer_customer_id' => 'IC014', 'sage_vendor_id' => 'IP009'],
            ['code' => 'AIG', 'gl_liaiblity_account' => '55130', 'sage_insurer_customer_id' => 'IC016', 'sage_vendor_id' => 'IP015'],
            ['code' => 'ECI', 'gl_liaiblity_account' => '55520', 'sage_insurer_customer_id' => 'IC051', 'sage_vendor_id' => 'IP052'],
        ];

        foreach ($insuranceProvidersMapping as $providerMapping) {
            $insuranceProvider = InsuranceProvider::where('code', $providerMapping['code'])->first();

            if ($insuranceProvider && ($insuranceProvider->sage_vendor_id != $providerMapping['sage_vendor_id'] ||
                $insuranceProvider->gl_liaiblity_account != $providerMapping['gl_liaiblity_account'] ||
                $insuranceProvider->sage_insurer_customer_id != $providerMapping['sage_insurer_customer_id'])) {

                $insuranceProvider->update([
                    'sage_vendor_id' => $providerMapping['sage_vendor_id'],
                    'gl_liaiblity_account' => $providerMapping['gl_liaiblity_account'],
                    'sage_insurer_customer_id' => $providerMapping['sage_insurer_customer_id'],
                ]);

            }

        }

    }
}
