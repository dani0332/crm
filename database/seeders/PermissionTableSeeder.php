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


        //Transection
        \DB::table('permissions')->insertGetId([
            'name'=>'transection-list',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'transection-create',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'transection-edit',
            'guard_name'=>'web',
        ]);
        \DB::table('permissions')->insertGetId([
            'name'=>'transection-delete',
            'guard_name'=>'web',
        ]);
    }
}
