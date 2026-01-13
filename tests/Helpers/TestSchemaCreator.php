<?php

namespace Tests\Helpers;

use Tests\Support\Schema\CoreSchema;
use Tests\Support\Schema\RenewalsSchema;

class TestSchemaCreator
{
    /**
     * Create minimal required tables tests.
     */
    public static function createMinimalSchema(): void
    {
        (new CoreSchema)->register();
    }

    public static function createRenewalsSchema(): void
    {
        self::createMinimalSchema();

        (new RenewalsSchema)->register();
    }

    /**
     * Create minimal required tables/columns for UserController@update tests.
     *
     * This extends the existing minimal schema with user-management specific
     * tables/columns needed by the update flow, relationships, and Bird email
     * dispatch path.
     */
    public static function createUserManagementSchema(): void
    {
        self::createMinimalSchema();

        $schema = Schema::connection('sqlite');

        // Extend users table with columns used in UserController@update
        if ($schema->hasTable('users')) {
            $schema->table('users', function ($table) use ($schema) {
                if (! $schema->hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(1);
                }
                if (! $schema->hasColumn('users', 'mobile_no')) {
                    $table->string('mobile_no')->nullable();
                }
                if (! $schema->hasColumn('users', 'landline_no')) {
                    $table->string('landline_no')->nullable();
                }
                if (! $schema->hasColumn('users', 'calendar_link')) {
                    $table->string('calendar_link')->nullable();
                }
                if (! $schema->hasColumn('users', 'phone_calendar_link')) {
                    $table->string('phone_calendar_link')->nullable();
                }
                if (! $schema->hasColumn('users', 'department_id')) {
                    $table->unsignedBigInteger('department_id')->nullable();
                }
                if (! $schema->hasColumn('users', 'rm_category_id')) {
                    $table->unsignedBigInteger('rm_category_id')->nullable();
                }
                if (! $schema->hasColumn('users', 'additional_team_ids')) {
                    $table->text('additional_team_ids')->nullable();
                }
                if (! $schema->hasColumn('users', 'sub_team_id')) {
                    $table->unsignedBigInteger('sub_team_id')->nullable();
                }
                // Used by CheckLastLoginMiddleware (last_login_check)
                if (! $schema->hasColumn('users', 'last_login')) {
                    $table->timestamp('last_login')->nullable();
                }
            });
        }

        // Extend teams table for TeamHierarchyTrait::getAllProducts()
        if ($schema->hasTable('teams')) {
            $schema->table('teams', function ($table) use ($schema) {
                if (! $schema->hasColumn('teams', 'type')) {
                    $table->unsignedTinyInteger('type')->nullable();
                }
                if (! $schema->hasColumn('teams', 'is_active')) {
                    $table->boolean('is_active')->default(1);
                }
                if (! $schema->hasColumn('teams', 'parent_team_id')) {
                    $table->unsignedBigInteger('parent_team_id')->nullable();
                }
            });
        }

        // Pivot: user_manager (subordinates <-> managers)
        if (! $schema->hasTable('user_manager')) {
            $schema->create('user_manager', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('manager_id');
                $table->timestamps();
            });
        }

        // Departments + pivot used by DepartmentService::syncUserDepartments()
        if (! $schema->hasTable('departments')) {
            $schema->create('departments', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('user_departments')) {
            $schema->create('user_departments', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('department_id');
                $table->timestamps();
            });
        }

        // Business types + pivot used by User::businessTypes()->sync()
        if (! $schema->hasTable('business_type_of_insurance')) {
            $schema->create('business_type_of_insurance', function ($table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('business_type_of_insurance_user')) {
            $schema->create('business_type_of_insurance_user', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('business_type_of_insurance_id');
                $table->timestamps();
            });
        }

        // user_products table used in update() (sync via DB::table inserts)
        if (! $schema->hasTable('user_products')) {
            $schema->create('user_products', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('product_id');
                $table->timestamps();
            });
        }
    }
}
