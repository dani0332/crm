<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Roles
        \DB::table('permissions')->insertGetId([
            'name'=>'role-list',
            'guard_name'=>'web',
        ]);

        \DB::table('permissions')->insertGetId([
            'name'=>'role-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'role-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'role-delete',
            'guard_name'=>'web',
        ]);

        //Users
        \DB::table('permissions')->insertGetId([
            'name'=>'users-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'users-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'users-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'users-delete',
            'guard_name'=>'web',
        ]);


        //partners
        \DB::table('permissions')->insertGetId([
            'name'=>'partners-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'partners-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'partners-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'partners-delete',
            'guard_name'=>'web',
        ]);

        //rewards
        \DB::table('permissions')->insertGetId([
            'name'=>'rewards-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'rewards-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'rewards-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'rewards-delete',
            'guard_name'=>'web',
        ]);

        //rewards-categories
        \DB::table('permissions')->insertGetId([
            'name'=>'reward-categories-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'reward-categories-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'reward-categories-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'reward-categories-delete',
            'guard_name'=>'web',
        ]);


        //rewards-tags
        \DB::table('permissions')->insertGetId([
            'name'=>'reward-tags-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'reward-tags-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'reward-tags-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'reward-tags-delete',
            'guard_name'=>'web',
        ]);



        //car-quotes
        \DB::table('permissions')->insertGetId([
            'name'=>'car-quotes-list',
            'guard_name'=>'web',
        ]);

        \DB::table('permissions')->insertGetId([
            'name'=>'car-quotes-resubmit-api',
            'guard_name'=>'web',
        ]);

        \DB::table('permissions')->insertGetId([
            'name'=>'customers-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'customers-edit',
            'guard_name'=>'web',
        ]);


        //insurance company

        \DB::table('permissions')->insertGetId([
            'name'=>'insurance-company-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'insurance-company-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'insurance-company-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'insurance-company-delete',
            'guard_name'=>'web',
        ]);

        //Handler company

        \DB::table('permissions')->insertGetId([
            'name'=>'handler-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'handler-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'handler-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'handler-delete',
            'guard_name'=>'web',
        ]);

        //Reason

        \DB::table('permissions')->insertGetId([
            'name'=>'reason-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'reason-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'reason-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'reason-delete',
            'guard_name'=>'web',
        ]);

        //Status
        \DB::table('permissions')->insertGetId([
            'name'=>'status-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'status-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'status-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'status-delete',
            'guard_name'=>'web',
        ]);


        //Payment Modes
        \DB::table('permissions')->insertGetId([
            'name'=>'payment-mode-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'payment-mode-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'payment-mode-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'payment-mode-delete',
            'guard_name'=>'web',
        ]);


        //Transaction
        \DB::table('permissions')->insertGetId([
            'name'=>'transapp-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'transapp-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'transapp-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'transapp-delete',
            'guard_name'=>'web',
        ]);

        //Claims
        \DB::table('permissions')->insertGetId([
            'name'=>'claim-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'claim-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'claim-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'claim-delete',
            'guard_name'=>'web',
        ]);

        //Type of Insurance
        \DB::table('permissions')->insertGetId([
            'name'=>'type-of-insurance-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'type-of-insurance-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'type-of-insurance-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'type-of-insurance-delete',
            'guard_name'=>'web',
        ]);

        //Sub Type of Insurance
        \DB::table('permissions')->insertGetId([
            'name'=>'sub-type-of-insurance-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'sub-type-of-insurance-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'sub-type-of-insurance-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'sub-type-of-insurance-delete',
            'guard_name'=>'web',
        ]);

        //Claims Status
        \DB::table('permissions')->insertGetId([
            'name'=>'claims-status-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'claims-status-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'claims-status-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'claims-status-delete',
            'guard_name'=>'web',
        ]);

        //Car Repair Coverage
        \DB::table('permissions')->insertGetId([
            'name'=>'car-repair-coverage-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'car-repair-coverage-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'car-repair-coverage-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'car-repair-coverage-delete',
            'guard_name'=>'web',
        ]);

        //Car Repair Type
        \DB::table('permissions')->insertGetId([
            'name'=>'car-repair-type-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'car-repair-type-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'car-repair-type-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'car-repair-type-delete',
            'guard_name'=>'web',
        ]);

        //Rent a Car
        \DB::table('permissions')->insertGetId([
            'name'=>'rent-a-car-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'rent-a-car-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'rent-a-car-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'rent-a-car-delete',
            'guard_name'=>'web',
        ]);



    }
}