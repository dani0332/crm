<?php

namespace Database\Seeders;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Models\InsuranceProvider;
use App\Models\Lookup;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Seeder;

class CarAdditionalDetailsForLivaSeeder extends Seeder
{
    private $insuranceProviderId;

    public function __construct()
    {
        $this->insuranceProviderId = InsuranceProvider::where('code', InsuranceProvidersEnum::RSA)
            ->select(['id', 'code'])
            ->first()->id;

        LoggerService::info(self::class.' fn: '.__FUNCTION__, extra: [
            'insurance_provider_id' => $this->insuranceProviderId,
            'insurance_provider_code' => InsuranceProvidersEnum::RSA,
        ]);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->plateCode();
        $this->rtaTransactionType();
        $this->rtaPlateCategory();
        $this->vehicleColor();
        $this->bankName();
        $this->annualMileageEstimate();
    }

    private function plateCode()
    {
        $plateCodes = [
            'A', 'AA', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'WHITE', 'X', 'Y', 'Z', 'BB', 'CC', 'DD', 'EE', 'HH', 'MM', 'NN',
        ];

        foreach ($plateCodes as $plateCode) {
            Lookup::firstOrCreate([
                'quote_type_id' => QuoteTypeId::Car,
                'key' => LookupsEnum::PLATE_CODE,
                'code' => $plateCode,
                'text' => $plateCode,
                'insurance_provider_id' => $this->insuranceProviderId,
            ], [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function rtaTransactionType()
    {
        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_TRANSACTION_TYPE,
            'code' => '10',
            'text' => 'Registration of new vehicle',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_TRANSACTION_TYPE,
            'code' => '20',
            'text' => 'Changing vehicle ownership (current registration valid)',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_TRANSACTION_TYPE,
            'code' => '30',
            'text' => 'Changing vehicle ownership (current registration to expire)',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_TRANSACTION_TYPE,
            'code' => '40',
            'text' => 'Renewal of vehicle (with current number plate)',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_TRANSACTION_TYPE,
            'code' => '50',
            'text' => 'Renewal of vehicle (with new number plate)',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_TRANSACTION_TYPE,
            'code' => '60',
            'text' => 'Update Registration Information',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_TRANSACTION_TYPE,
            'code' => '70',
            'text' => 'Export Certificate',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function rtaPlateCategory()
    {
        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_PLATE_CATEGORY,
            'code' => '14',
            'text' => 'Private',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_PLATE_CATEGORY,
            'code' => '18',
            'text' => 'Motorcycle',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_PLATE_CATEGORY,
            'code' => '39',
            'text' => 'Delegate',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_PLATE_CATEGORY,
            'code' => '42',
            'text' => 'Consulate Authority',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_PLATE_CATEGORY,
            'code' => '45',
            'text' => 'Classical',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::RTA_PLATE_CATEGORY,
            'code' => '36',
            'text' => 'Dubai Flag',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function vehicleColor()
    {
        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::VEHICLE_COLOR,
            'code' => '1',
            'text' => 'WHITE',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::VEHICLE_COLOR,
            'code' => '2',
            'text' => 'BLACK',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::VEHICLE_COLOR,
            'code' => '3',
            'text' => 'RED',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::VEHICLE_COLOR,
            'code' => '4',
            'text' => 'BLUE',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::VEHICLE_COLOR,
            'code' => '5',
            'text' => 'YELLOW',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::VEHICLE_COLOR,
            'code' => '6',
            'text' => 'GREEN',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::VEHICLE_COLOR,
            'code' => '7',
            'text' => 'Brown',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::VEHICLE_COLOR,
            'code' => '8',
            'text' => 'SILVER',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::VEHICLE_COLOR,
            'code' => '9',
            'text' => 'Bronze',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function bankName()
    {
        $banks = [
            ['51155', 'gargash-enterprises', 'Gargash Enterprises'],
            ['51005', 'emirates-bank', 'Emirates Bank'],
            ['51006', 'citibank', 'Citibank'],
            ['51158', 'the-petroleum-institute-of-abu-dhabi', 'The Petroleum Institute Of Abu Dhabi'],
            ['51084', 'etisalat', 'Etisalat'],
            ['51152', 'al-khaliji-france', 'Al Khaliji France'],
            ['51022', 'arab-african-international-bank', 'Arab African International Bank'],
            ['51090', 'sharjah-islamic-bank', 'Sharjah Islamic Bank'],
            ['51093', 'liberty-automobiles-co-llc', 'Liberty Automobiles Co. Llc'],
            ['51107', 'abu-dhabi-investment-authority', 'Abu Dhabi Investment Authority'],
            ['51119', 'takreer-abu-dhabi-oil-refining-company', 'Takreer (Abu Dhabi Oil Refining Company)'],
            ['51124', 'emirates-airlines-or-hsbc-mefco', 'Emirates Airlines &/Or Hsbc Mefco'],
            ['51134', 'ajman-bank', 'Ajman Bank'],
            ['51145', 'adnoc', 'Adnoc'],
            ['51138', 'al-badr-islamic-finance-company', 'Al Badr Islamic Finance Company'],
            ['51163', 'emirates-motor-company', 'Emirates Motor Company'],
            ['51173', 'commercial-bank-international', 'Commercial Bank International'],
            ['51167', 'emirates-nuclear-energy-corporation', 'Emirates Nuclear Energy Corporation'],
            ['51169', 'al-futtaim-finance-company', 'Al Futtaim Finance Company'],
            ['99934', 'abu-dhabi-commercial-islamic-finance', 'Abu Dhabi Commercial Islamic Finance'],
            ['51185', 'ruby-diamond-investments-limited', 'Ruby Diamond Investments Limited'],
            ['51127', 'emirates-airlines-national-bank-of-dubai', 'Emirates Airlines / National Bank Of Dubai'],
            ['99910', 'bahrain-house-finance', 'Bahrain House Finance'],
            ['99946', 'al-futtaim-finance', 'AL FUTTAIM FINANCE'],
            ['99932', 'noor-islamic-bank', 'Noor Islamic Bank'],
            ['60092', 'inter-emirates-motors-sole-proprietorship-llc', 'INTER EMIRATES MOTORS SOLE PORPRIETORSHIP LLC'],
            ['99950', 'el-nilein-bank', 'EL NILEIN BANK'],
            ['51040', 'bnp-paribas', 'Bnp Paribas'],
            ['51099', 'doha-bank', 'Doha Bank'],
            ['51003', 'standard-chartered-bank', 'Standard Chartered Bank'],
            ['51009', 'habib-bank', 'Habib Bank'],
            ['51012', 'dubai-commercial-bank', 'Dubai Commercial Bank'],
            ['99908', 'emirates-money', 'Emirates Money'],
            ['51030', 'bank-of-sharjah', 'Bank Of Sharjah'],
            ['51038', 'banque-paribas', 'Banque Paribas'],
            ['51039', 'barclays-bank-plc', 'Barclays Bank Plc'],
            ['51075', 'deutsche-bank-ag', 'Deutsche Bank Ag'],
            ['51079', 'national-bank-of-sharjah', 'National Bank Of Sharjah'],
            ['51139', 'duniatrade', 'Duniatrade'],
            ['51095', 'hsbc-amanah', 'Hsbc Amanah'],
            ['51102', 'al-habtoor-motors-co-llc', 'Al Habtoor Motors Co Llc'],
            ['51116', 'maf-orix-finance-pjsc', 'Maf Orix Finance Pjsc'],
            ['51121', 'dynatrade', 'Dynatrade'],
            ['51141', 'emirates-airlines-or-emirates-islamic-bank', 'Emirates Airlines &/Or Emirates Islamic Bank'],
            ['51142', 'yaseer-car-rental', 'Yaseer Car Rental'],
            ['51143', 'gargash-motors', 'Gargash Motors'],
            ['51174', 'mashreq-al-islami', 'Mashreq Al Islami'],
            ['60068', 'oasis-enterprises-llc', 'Oasis Enterprises Llc'],
            ['51176', 'emirates-airlines-emirates-nbd', 'Emirates Airlines / Emirates Nbd'],
            ['60067', 'al-buhaira-national-insurance-co-abnic', 'Al Buhaira National Insurance Co (Abnic)'],
            ['51170', 'aafaq-islamic-finance-company', 'Aafaq Islamic Finance Company'],
            ['51182', 'bmw-albatha-finance', 'BMW Albatha Finance'],
            ['51187', 'tamweel', 'Tamweel'],
            ['51190', 'hsbc', 'HSBC'],
            ['51199', 'belhasa-motors', 'Belhasa Motors'],
            ['51135', 'al-khoory-automobile-llc', 'Al Khoory Automobile Llc'],
            ['51034', 'banque-banorabe', 'Banque Banorabe'],
            ['51037', 'banque-nationale-de-paris', 'Banque Nationale De Paris'],
            ['51047', 'emirates-national-bank', 'Emirates National Bank'],
            ['51073', 'core-states-bank', 'Core States Bank'],
            ['51120', 'trading-enterprises', 'Trading Enterprises'],
            ['99906', 'advanced-technology-investment-company-atic', 'Advanced Technology Investment Company (Atic)'],
            ['51010', 'habib-bank-ag-zurich', 'Habib Bank Ag Zurich'],
            ['51011', 'abu-dhabi-commercial-bank', 'Abu Dhabi Commercial Bank'],
            ['51013', 'national-bank-of-abu-dhabi', 'National Bank Of Abu Dhabi'],
            ['51018', 'alahli-bank-of-kuwait', 'Alahli Bank Of Kuwait'],
            ['51019', 'american-express', 'American Express'],
            ['51024', 'arab-bank', 'Arab Bank'],
            ['51060', 'rak-bank', 'RAK Bank'],
            ['51070', 'bank-of-bahrain-kuwait', 'Bank Of Bahrain & Kuwait'],
            ['51082', 'uae-central-bank', 'U.A.E. Central Bank'],
            ['51133', 'royal-bank-of-scotland', 'Royal Bank Of Scotland'],
            ['51137', 'al-ghandi-auto', 'Al Ghandi Auto'],
            ['51115', 'gulf-finance-corporation', 'Gulf Finance Corporation'],
            ['51122', 'emirates-dnata', 'Emirates / Dnata'],
            ['51125', 'emirates-airlines-or-lloyds-tsb-bank-plc', 'Emirates Airlines &/Or Lloyds Tsb Bank Plc'],
            ['51085', 'mawarid-finance', 'Mawarid Finance'],
            ['51153', 'emirates-money-consumer-finance-1', 'Emirates Money Consumer Finance'],
            ['51148', 'emirates-money-consumer-finance-2', 'Emirates Money Consumer Finance'],
            ['51179', 'commercial-bank-of-dubai-attijari-al-islami', 'Commercial Bank Of Dubai - Attijari Al Islami'],
            ['51180', 'al-hail-orix-finance', 'Al Hail Orix Finance'],
            ['51200', 'al-rostamani-trading-company-llc', 'Al Rostamani Trading Company LLC'],
            ['99937', 'mdc-business-management-services', 'MDC Business Management Services'],
            ['51184', 'national-auto', 'National Auto'],
            ['99935', 'first-abu-dhabi-bank', 'FIRST ABU DHABI BANK'],
            ['51194', 'blom-bank-france', 'Blom Bank France'],
            ['51149', 'ajman-bank-2', 'Ajman Bank'],
            ['51089', 'dnata', 'Dnata'],
            ['99947', 'al-futtaim-finance-pvjsc', 'AL FUTTAIM FINANCE PVJSC'],
            ['60091', 'mohamed-abdulrahman-al-bahar-llc', 'Mohamed Abdulrahman Al-Bahar LLC'],
            ['100002', 'nawah-energy', 'Nawah Energy'],
            ['60088', 'gb-equipment-solutions-llc-dubai', 'GB Equipment solutions LLC, Dubai'],
            ['60093', 'al-habtoor-royal-car-llc', 'Al Habtoor Royal Car LLC'],
            ['51026', 'banca-commerciale-italiana', 'Banca Commerciale Italiana'],
            ['51045', 'dubai-bank', 'Dubai Bank'],
            ['51052', 'invest-bank', 'Invest Bank'],
            ['51054', 'lloyds-tsb-bank', 'Lloyds TSB Bank'],
            ['51059', 'rafidain-bank', 'Rafidain Bank'],
            ['51071', 'banque-indosuez', 'Banque Indosuez'],
            ['51104', 'al-nabooda-automobiles-llc', 'Al Nabooda Automobiles Llc'],
            ['51014', 'sharjah-bank', 'Sharjah Bank'],
            ['51023', 'arab-bank-for-investment-foreign-trade', 'Arab Bank For Investment & Foreign Trade'],
            ['51028', 'bank-of-baroda', 'Bank Of Baroda'],
            ['51049', 'hamid-bank-limited', 'Hamid Bank Limited'],
            ['51055', 'middle-east-bank', 'Middle East Bank'],
            ['51056', 'natexis-banques-populaires', 'Natexis Banques Populaires'],
            ['51063', 'union-national-bank', 'Union National Bank'],
            ['51072', 'cedel-bank', 'Cedel Bank'],
            ['51140', 'dunia-finance', 'Dunia Finance'],
            ['51160', 'attijari-al-islami', 'Attijari Al Islami'],
            ['99911', 'abudhabi-future-energy-company', 'Abudhabi Future Energy Company'],
            ['51100', 'al-yousuf-llc', 'Al Yousuf Llc'],
            ['51105', 'adma-opco', 'Adma Opco'],
            ['51108', 'noor-bank-1', 'Noor Bank'],
            ['51112', 'blc-bank', 'Blc Bank'],
            ['51113', 'blom-bank', 'Blom Bank'],
            ['51147', 'gargash-enterprises-llc', 'Gargash Enterprises Llc'],
            ['51161', 'borouge-abu-dhabi-polymers-co-ltd', 'Borouge Abu Dhabi Polymers Co Ltd'],
            ['51131', 'al-hilal-bank', 'Al Hilal Bank'],
            ['99913', 'arabian-gulf-mechanical-centre', 'Arabian Gulf Mechanical Centre'],
            ['51171', 'mubadala-ge-capital-pjsc', 'Mubadala Ge Capital P.J.S.C'],
            ['99933', 'bmw-albatha-finance-2', 'BMW Albatha Finance'],
            ['51197', 'al-shirawi-enterprises-llc', 'AL SHIRAWI ENTERPRISES LLC'],
            ['99912', 'afge-money', 'Afge Money'],
            ['51130', 'al-noor-islamic-bank', 'Al Noor Islamic Bank'],
            ['51165', 'emirates-investment-authority', 'Emirates Investment Authority'],
            ['51177', 'al-majid-motors', 'Al Majid Motors'],
            ['51057', 'national-bank-of-bahrain', 'National Bank Of Bahrain'],
            ['51097', 'badr-al-islami', 'Badr Al Islami'],
            ['51101', 'al-tayer-motors-llc', 'Al Tayer Motors Llc'],
            ['51109', 'amlak-finance', 'Amlak Finance'],
            ['51111', 'arab-bank-for-investment-foreign-trade-2', 'Arab Bank For Investment & Foreign Trade'],
            ['51002', 'mashreq-bank', 'Mashreq Bank'],
            ['51015', 'national-bank-of-dubai', 'National Bank Of Dubai'],
            ['51017', 'abu-dhabi-islamic-bank', 'Abu Dhabi Islamic Bank'],
            ['51033', 'bank-saderat-iran', 'Bank Saderat Iran'],
            ['51044', 'credit-suisse', 'Credit Suisse'],
            ['51051', 'ing-asia-private-bank', 'Ing Asia Provate Bank'],
            ['51053', 'janata-bank', 'Janata Bank'],
            ['51058', 'national-bank-of-umm-al-qaiwain', 'National Bank of Umm Al-Qaiwain'],
            ['51069', 'bank-muscat-al-ahli-al-omani', 'Bank Muscat Al Ahli Al Omani'],
            ['51074', 'dresdner-bank', 'Dresdner Bank'],
            ['51080', 'philippine-national-bank', 'Philippine National Bank'],
            ['51092', 'juma-al-majid-est', 'Juma Al Majid Est'],
            ['51096', 'finance-house', 'Finance House'],
            ['51117', 'mubadala-development-company', 'Mubadala Development Company'],
            ['51128', 'national-bank-of-ras-al-khaimah', 'National Bank Of Ras Al Khaimah'],
            ['99909', 'galadari-automobiles-co-ltd-llc', 'Galadari Automobiles Co Ltd. - L.L.C.'],
            ['51178', 'mortgag-tto-al-ttigari-al-islami', 'Mortgag Tto ( Al Ttigari Al Islami)'],
            ['99915', 'others', 'Others'],
            ['51159', 'al-wifaq-finance-company', 'Al Wifaq Finance Company'],
            ['51168', 'al-masaood-automobiles', 'Al Masaood Automobiles'],
            ['60089', 'al-futtaim-auto-machinery-llc-famco', 'Al Futtaim Auto & Machinery LLC (FAMCO)'],
            ['51191', 'adcb-islamic-banking', 'ADCB ISLAMIC BANKING'],
            ['99948', 'deem-finance', 'Deem Finance'],
            ['51050', 'icici-bank', 'Icici Bank'],
            ['51062', 'union-bancaire-privee', 'Union Bancaire Privee'],
            ['51001', 'middle-east-finance-co', 'Middle East Finance Co'],
            ['51020', 'ansbacher-middle-east', 'Ansbacher Middle East'],
            ['51027', 'bank-melli-iran', 'Bank Melli Iran'],
            ['51043', 'commercial-bank-of-dubai', 'Commercial Bank Of Dubai'],
            ['51046', 'emirates-industrial-bank', 'Emirates Industrial Bank'],
            ['51048', 'first-gulf-bank', 'First Gulf Bank'],
            ['51151', 'abu-dhabi-investment-council', 'Abu Dhabi Investment Council'],
            ['51154', 'abu-dhabi-national-islamic-finance', 'Abu Dhabi National Islamic Finance'],
            ['51150', 'emirates-nbd', 'Emirates NBD'],
            ['51162', 'khalifa-fund-for-enterprise-development', 'Khalifa Fund For Enterprise Development'],
            ['51098', 'dubai-petroleum-company', 'Dubai Petroleum Company'],
            ['51144', 'emirates-investment-authority-2', 'Emirates Investment Authority'],
            ['51156', 'reem-finance', 'Reem Finance'],
            ['51088', 'emirates-airlines-abu-dhabi-commercial-bank', 'Emirates Airlines / Abu Dhabi Commercial Bank'],
            ['51183', 'miral-asset-management-llc', 'Miral Asset Management LLC'],
            ['51186', 'united-diesel-llc', 'United Diesel LLC'],
            ['99928', 'habib-bank-limited', 'Habib bank Limited'],
            ['99938', 'refrigerated-transport-systems-llc', 'Refrigerated Transport Systems (LLC)'],
            ['51198', 'al-futtaim-auto-machinery-company-llc', 'Al Futtaim Auto & Machinery Company (L.L.C)'],
            ['51189', 'emirates-investment-bank', 'Emirates Investment Bank'],
            ['999512', 'emirates-steel-industries-co-pjs', 'Emirates Steel Industries Co PJS'],
            ['51146', 'emirates-airline-duniya-finance-llc', 'Emirates Airline / Duniya Finance Llc'],
            ['51172', 'ek-airlines-and-al-hilal-bank', 'Ek Airlines And Al Hilal Bank'],
            ['99914', 'etihad-rail', 'Etihad Rail'],
            ['99931', 'gasco', 'GASCO'],
            ['51195', 'bmw-albatha-finance-psc', 'BMW Albatha Finance PSC'],
            ['56007', 'al-futtaim-motors-company-llc', 'AL FUTTAIM MOTORS COMPANY LLC'],
            ['51042', 'commercial-bank-international-plc', 'Commercial Bank International Plc'],
            ['51066', 'emirates-islamic', 'Emirates Islamic'],
            ['51068', 'bank-brussels-lambert', 'Bank Brussels Lambert'],
            ['51078', 'merrill-lynch-bank-suisse', 'Merrill Lynch Bank Suisse'],
            ['51081', 'societe-generali', 'Societe Generali'],
            ['51118', 'utmost-building-materials-llc', 'Utmost Building Materials Llc'],
            ['51123', 'emirates-airlines-or-emirates-bank-international-pjsc', 'Emirates Airlines &/Or Emirates Bank International Pjsc'],
            ['51004', 'osool-finance-company', 'Osool Finance Company'],
            ['51008', 'dubai-islamic-bank', 'Dubai Islamic Bank'],
            ['51016', 'abn-amro', 'Abn Amro'],
            ['51021', 'anz-grindlays-bank', 'Anz Grindlays Bank'],
            ['51025', 'arab-emirates-investment-bank', 'Arab Emirates Investment Bank'],
            ['51031', 'bank-of-the-arab-coast', 'Bank Of The Arab Coast'],
            ['51035', 'banque-du-caire', 'Banque Du Caire'],
            ['51041', 'calyon-bank', 'Calyon Bank'],
            ['51065', 'united-bank', 'United Bank'],
            ['51077', 'investment-bank-for-trade-finance', 'Investment Bank For Trade & Finance'],
            ['51103', 'al-masraf-bank', 'Al Masraf Bank'],
            ['51110', 'arabian-automobiles-company', 'Arabian Automobiles Company'],
            ['51126', 'emirates-airlines-or-mashreq-bank', 'Emirates Airlines &/Or Mashreq Bank'],
            ['51164', 'swaidan-trading-co-llc', 'Swaidan Trading Co. Llc'],
            ['51129', 'emirates-bank-or-lloyds-tsb-bank-plc', 'Emirates Bank &/Or Lloyds Tsb Bank Plc'],
            ['51136', 'omeir-bin-youssef-and-sons-llc', 'Omeir Bin Youssef And Sons Llc'],
            ['99930', 'hsbc-middle-east-finance-company', 'HSBC Middle East Finance Company'],
            ['51188', 'hsbc-bank-middle-east-ltd', 'HSBC Bank Middle East Ltd'],
            ['51166', 'samba-bank', 'Samba Bank'],
            ['51192', 'abu-dhabi-finance', 'Abu Dhabi Finance'],
            ['100000', 'union-bank-of-india', 'Union Bank of India'],
            ['100003', 'juma-al-majid-co-llc', 'JUMA AL MAJID Co LLC'],
            ['51201', 'kanoo-machinery-llc', 'Kanoo Machinery LLC'],
            ['99949', 'banque-banorient-france', 'Banque Banorient France'],
            ['51076', 'hsbc-financial-services', 'Hsbc Financial Services'],
            ['51091', 'national-bank-of-fujairah', 'National Bank Of Fujairah'],
            ['51094', 'knowledge-management-intl', 'Knowledge Management Int\'L'],
            ['51202', 'al-fahim-motors', 'Al Fahim Motors'],
            ['51157', 'higher-corporation-for-specialized-economic-zone', 'Higher Corporation For Specialized Economic Zone'],
            ['51036', 'banque-libanaise-pour-le-commerce', 'Banque Libanaise Pour Le Commerce'],
            ['51061', 'royal-bank-of-canada', 'Royal Bank Of Canada'],
            ['51067', 'ubs-ag', 'Ubs Ag'],
            ['51083', 'emirates-airlines-2', 'Emirates Airlines'],
            ['99907', 'islamic-finance-company', 'Islamic Finance Company'],
            ['51087', 'badr-al-islami-2', 'Badr Al Islami'],
            ['51175', 'rak-bank-islamic-division', 'Rak Bank - Islamic Division'],
            ['51181', 'banque-misr', 'Banque Misr'],
            ['99929', 'national-bank-of-abudhabi', 'National Bank of Abudhabi'],
            ['51196', 'dalma-motors-llc', 'Dalma Motors LLC'],
            ['51193', 'emirates-islamic-bank', 'Emirates Islamic Bank'],
            ['51132', 'hilal-bank', 'Hilal Bank'],
            ['51086', 'noor-bank-2', 'Noor Bank'],
            ['56004', 'united-motor-heavy-equipment-co-llc', 'United Motor & Heavy Equipment CO. LLC'],
            ['51029', 'bank-of-credit-commerce-international', 'Bank Of Credit & Commerce International'],
            ['51032', 'bank-of-tokyo', 'Bank Of Tokyo'],
            ['60094', 'ali-sons-holdings-llc', 'Ali & Sons Holdings LLC'],
            ['51064', 'united-arab-bank', 'United Arab Bank'],
            ['51106', 'al-futtaim-motors', 'Al Futtaim Motors'],
            ['51114', 'general-navigation-and-commerce-company-llc', 'General Navigation And Commerce Company Llc'],
        ];

        foreach ($banks as $bank) {
            Lookup::firstOrCreate([
                'quote_type_id' => QuoteTypeId::Car,
                'key' => LookupsEnum::BANK_NAME,
                'code' => $bank[0],
                'text' => $bank[2],
                'insurance_provider_id' => $this->insuranceProviderId,
            ], [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function annualMileageEstimate()
    {
        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::ANNUAL_MILEAGE_ESTIMATE,
            'code' => '1',
            'text' => 'Less than 8,000 KM',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::ANNUAL_MILEAGE_ESTIMATE,
            'code' => '2',
            'text' => '8,000- 20,000 KM',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::ANNUAL_MILEAGE_ESTIMATE,
            'code' => '3',
            'text' => '20,000- 40,000 KM',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::ANNUAL_MILEAGE_ESTIMATE,
            'code' => '4',
            'text' => '40,000- 60,000 KM',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::ANNUAL_MILEAGE_ESTIMATE,
            'code' => '5',
            'text' => '60,000 -100,000 KM',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => LookupsEnum::ANNUAL_MILEAGE_ESTIMATE,
            'code' => '6',
            'text' => '> 100,000 KM',
            'insurance_provider_id' => $this->insuranceProviderId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
