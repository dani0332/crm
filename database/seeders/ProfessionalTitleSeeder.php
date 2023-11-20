<?php

namespace Database\Seeders;

use App\Enums\LookupsEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfessionalTitleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! DB::table('lookups')->where('key', LookupsEnum::PROFESSIONAL_TITLE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'accountant', 'text' => 'Accountant', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'actor-actress', 'text' => 'Actor / Actress', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'air-traffic-controller', 'text' => 'Air Traffic Controller', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'architect', 'text' => 'Architect', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'artist', 'text' => 'Artist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'auditor', 'text' => 'Auditor', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'businessman', 'text' => 'Businessman', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'businesswoman', 'text' => 'Businesswoman', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'carpenter', 'text' => 'Carpenter', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'cashier', 'text' => 'Cashier', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'designer', 'text' => 'Designer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'doctor', 'text' => 'Doctor', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'editor', 'text' => 'Editor', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'educator-teacher', 'text' => 'Educator / Teacher', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'electrician', 'text' => 'Electrician', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'engineer', 'text' => 'Engineer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'entertainer', 'text' => 'Entertainer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'farmer', 'text' => 'Farmer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'host-babysitter-day-care-worker', 'text' => 'Host Babysitter / Day Care Worker', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'house-wife', 'text' => 'House Wife', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'hr-manager', 'text' => 'HR Manager', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'lawyer', 'text' => 'Lawyer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'legal-secretary', 'text' => 'Legal Secretary', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'marketing-assistant-manager', 'text' => 'Marketing Assistant / Manager', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'marketing-coordinator', 'text' => 'Marketing Coordinator', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'military-service-person', 'text' => 'Military Service Person', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'musician', 'text' => 'Musician', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'nail-technician-perfumer', 'text' => 'Nail Technician / Perfumer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'nanny-maid', 'text' => 'Nanny / Child-Care Provider / Caregiver Maid / Domestic Worker', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'nurse', 'text' => 'Nurse', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'medical-assistant', 'text' => 'Medical Assistant', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'nutritionist', 'text' => 'Nutritionist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'painter', 'text' => 'Painter', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'pharmacist', 'text' => 'Pharmacist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'pharmacy-assistant', 'text' => 'Pharmacy Assistant', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'photographer', 'text' => 'Photographer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'physician', 'text' => 'Physician', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'pilot', 'text' => 'Pilot', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'plumber', 'text' => 'Plumber', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'police-officer', 'text' => 'Police Officer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'politician', 'text' => 'Politician', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'production-manager', 'text' => 'Production Manager', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'professor', 'text' => 'Professor', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'programmer', 'text' => 'Programmer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'promotions-manager', 'text' => 'Promotions Manager', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'psychologist', 'text' => 'Psychologist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'receptionist', 'text' => 'Receptionist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'reporter,-writer', 'text' => 'Reporter, Writer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'safety-officer', 'text' => 'Safety Officer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'sales-representative', 'text' => 'Sales Representative', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'secretary', 'text' => 'Secretary', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'student', 'text' => 'Student', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'teacher', 'text' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'therapist', 'text' => 'Therapist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'tour-guide', 'text' => 'Tour Guide', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
