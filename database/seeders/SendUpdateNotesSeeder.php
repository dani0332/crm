<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Models\Lookup;
use Illuminate\Database\Seeder;

class SendUpdateNotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'POLICY_START_DATE',
            'text' => 'Policy Start Date',
        ], [
            'description' => 'The policy start date has been updated.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'POLICY_END_DATE',
            'text' => 'Policy End Date',
        ], [
            'description' => 'The policy end date has been updated.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'PAB_DRIVER_COVER_INCLUDED',
            'text' => 'PAB - Driver Cover Included',
        ], [
            'description' => 'Personal accident benefit - Driver cover has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'PAB_DRIVER_COVER_EXCLUDED',
            'text' => 'PAB - Driver Cover Excluded',
        ], [
            'description' => 'Personal accident benefit - Driver cover has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'PAB_PASSENGER_COVER_INCLUDED',
            'text' => 'PAB - Passenger Cover Included',
        ], [
            'description' => 'Personal accident benefit - Passenger cover has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'PAB_PASSENGER_COVER_EXCLUDED',
            'text' => 'PAB - Passenger Cover Excluded',
        ], [
            'description' => 'Personal accident benefit - Passenger cover has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'ROADSIDE_ASSISTANCE_INCLUDED',
            'text' => 'Roadside Assistance Included',
        ], [
            'description' => 'Roadside assistance has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'ROADSIDE_ASSISTANCE_EXCLUDED',
            'text' => 'Roadside Assistance Excluded',
        ], [
            'description' => 'Roadside assistance has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'CAR_HIRE_COVER_INCLUDED',
            'text' => 'Car Hire Cover Included',
        ], [
            'description' => 'Car hire cover has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'CAR_HIRE_COVER_EXCLUDED',
            'text' => 'Car Hire Cover Excluded',
        ], [
            'description' => 'Car hire cover has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'GCC_COVER_INCLUDED',
            'text' => 'GCC Cover Included',
        ], [
            'description' => 'GCC cover has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'GCC_COVER_EXCLUDED',
            'text' => 'GCC Cover Excluded',
        ], [
            'description' => 'GCC cover has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'OMAN_COVER_INCLUDED',
            'text' => 'Oman Cover (Own Damage) Included',
        ], [
            'description' => 'Oman cover (Own Damage) has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'OMAN_COVER_EXCLUDED',
            'text' => 'Oman Cover (Own Damage) Excluded',
        ], [
            'description' => 'Oman cover (Own Damage) has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'CAR_VALUE_INCREASED',
            'text' => 'Car Value Increased',
        ], [
            'description' => 'The value of the car has been increased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'CAR_VALUE_DECREASED',
            'text' => 'Car Value Decreased',
        ], [
            'description' => 'The value of the car has been decreased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'OFF_ROAD_COVER_INCLUDED',
            'text' => 'Off road Cover Included',
        ], [
            'description' => 'Off road cover has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'OFF_ROAD_COVER_EXCLUDED',
            'text' => 'Off road Cover Excluded',
        ], [
            'description' => 'Off road cover has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'EMIRATE_OF_REGISTRATION_CHANGED',
            'text' => 'Emirate of Registration Changed',
        ], [
            'description' => 'The emirate of registration for the vehicle has been changed.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'SEATING_CAPACITY_INCREASED',
            'text' => 'Seating Capacity Increased',
        ], [
            'description' => 'The seating capacity of the vehicle has been increased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'SEATING_CAPACITY_DECREASED',
            'text' => 'Seating Capacity Decreased',
        ], [
            'description' => 'The seating capacity of the vehicle has been decreased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'ENGINE_CAPACITY_INCREASED',
            'text' => 'Engine Capacity Increased',
        ], [
            'description' => 'The engine capacity of the vehicle has been increased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'ENGINE_CAPACITY_DECREASED',
            'text' => 'Engine Capacity Decreased',
        ], [
            'description' => 'The engine capacity of the vehicle has been decreased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'CYLINDERS_INCREASED',
            'text' => 'No. of Cylinders Increased',
        ], [
            'description' => 'The number of cylinders in the vehicle has been increased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'CYLINDERS_DECREASED',
            'text' => 'No. of Cylinders Decreased',
        ], [
            'description' => 'The number of cylinders in the vehicle has been decreased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Car,
            'key' => 'car-su-notes',
            'code' => 'TYPE_OF_CAR_CHANGED',
            'text' => 'Type of Car Changed',
        ], [
            'description' => 'The type of car has been changed in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'POLICY_START_DATE',
            'text' => 'Policy Start Date',
        ], [
            'description' => 'The policy start date has been updated.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'POLICY_END_DATE',
            'text' => 'Policy End Date',
        ], [
            'description' => 'The policy end date has been updated.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'PAB_DRIVER_COVER_INCLUDED',
            'text' => 'PAB - Driver Cover Included',
        ], [
            'description' => 'Personal accident benefit - Driver cover has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'PAB_DRIVER_COVER_EXCLUDED',
            'text' => 'PAB - Driver Cover Excluded',
        ], [
            'description' => 'Personal accident benefit - Driver cover has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'PAB_PASSENGER_COVER_INCLUDED',
            'text' => 'PAB - Passenger Cover Included',
        ], [
            'description' => 'Personal accident benefit - Passenger cover has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'PAB_PASSENGER_COVER_EXCLUDED',
            'text' => 'PAB - Passenger Cover Excluded',
        ], [
            'description' => 'Personal accident benefit - Passenger cover has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'ROADSIDE_ASSISTANCE_INCLUDED',
            'text' => 'Roadside Assistance Included',
        ], [
            'description' => 'Roadside assistance has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'ROADSIDE_ASSISTANCE_EXCLUDED',
            'text' => 'Roadside Assistance Excluded',
        ], [
            'description' => 'Roadside assistance has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'OMAN_COVER_INCLUDED',
            'text' => 'Oman Cover (Orange Card) Included',
        ], [
            'description' => 'Oman cover (Orange Card) has been included in the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'OMAN_COVER_EXCLUDED',
            'text' => 'Oman Cover (Orange Card) Excluded',
        ], [
            'description' => 'Oman cover (Orange Card) has been excluded from the policy.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'BIKE_VALUE_INCREASED',
            'text' => 'Bike Value Increased',
        ], [
            'description' => 'The value of the bike has been increased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'BIKE_VALUE_DECREASED',
            'text' => 'Bike Value Decreased',
        ], [
            'description' => 'The value of the bike has been decreased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'EMIRATE_OF_REGISTRATION_CHANGED',
            'text' => 'Emirate of Registration Changed',
        ], [
            'description' => 'The emirate of registration for the vehicle has been changed.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'SEATING_CAPACITY_INCREASED',
            'text' => 'Seating Capacity Increased',
        ], [
            'description' => 'The seating capacity of the vehicle has been increased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'SEATING_CAPACITY_DECREASED',
            'text' => 'Seating Capacity Decreased',
        ], [
            'description' => 'The seating capacity of the vehicle has been decreased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'ENGINE_CAPACITY_INCREASED',
            'text' => 'Engine Capacity Increased',
        ], [
            'description' => 'The engine capacity of the vehicle has been increased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'ENGINE_CAPACITY_DECREASED',
            'text' => 'Engine Capacity Decreased',
        ], [
            'description' => 'The engine capacity of the vehicle has been decreased.',
            'updated_at' => now(),
        ]);

        Lookup::updateOrCreate([
            'quote_type_id' => QuoteTypeId::Bike,
            'key' => 'bike-su-notes',
            'code' => 'TYPE_OF_BIKE_CHANGED',
            'text' => 'Type of Bike Changed',
        ], [
            'description' => 'The type of bike has been changed in the policy.',
            'updated_at' => now(),
        ]);
    }
}
