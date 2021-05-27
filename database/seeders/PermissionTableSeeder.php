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



        //car-qoutes
        \DB::table('permissions')->insertGetId([
            'name'=>'car-qoutes-list',
            'guard_name'=>'web',
        ]);

        \DB::table('permissions')->insertGetId([
            'name'=>'car-qoutes-resubmit-api',
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

        

        
    }
}
