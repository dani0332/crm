<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Schema;
use Tests\Support\Schema\RenewalsSchema;

class TestSchemaCreator
{
    /**
     * Create minimal required tables for LifeQuote tests.
     */
    public static function createMinimalSchema(): void
    {
        // Create audits table if it doesn't exist (for Laravel Auditing)
        if (! Schema::connection('sqlite')->hasTable('audits')) {
            Schema::connection('sqlite')->create('audits', function ($table) {
                $table->id();
                $table->string('user_type')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('event');
                $table->morphs('auditable');
                $table->text('old_values')->nullable();
                $table->text('new_values')->nullable();
                $table->text('url')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('tags')->nullable();
                $table->timestamps();
            });
        }

        // Create users table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('users')) {
            Schema::connection('sqlite')->create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('remember_token')->nullable();
                $table->integer('status')->nullable();
                $table->timestamp('logout_at')->nullable();
                $table->timestamps();
            });
        }

        // Create roles table if it doesn't exist (for Spatie permissions)
        if (! Schema::connection('sqlite')->hasTable('roles')) {
            Schema::connection('sqlite')->create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // Create model_has_roles table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('model_has_roles')) {
            Schema::connection('sqlite')->create('model_has_roles', function ($table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            });
        }

        // Create permissions table if it doesn't exist (for Spatie permissions)
        if (! Schema::connection('sqlite')->hasTable('permissions')) {
            Schema::connection('sqlite')->create('permissions', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // Create model_has_permissions table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('model_has_permissions')) {
            Schema::connection('sqlite')->create('model_has_permissions', function ($table) {
                $table->id();
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            });
        }

        // Create role_has_permissions table if it doesn't exist (pivot table for Spatie permissions)
        if (! Schema::connection('sqlite')->hasTable('role_has_permissions')) {
            Schema::connection('sqlite')->create('role_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        // Create nationality table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('nationality')) {
            Schema::connection('sqlite')->create('nationality', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create currency_type table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('currency_type')) {
            Schema::connection('sqlite')->create('currency_type', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create marital_status table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('marital_status')) {
            Schema::connection('sqlite')->create('marital_status', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create life_insurance_purpose table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('life_insurance_purpose')) {
            Schema::connection('sqlite')->create('life_insurance_purpose', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create life_number_of_year table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('life_number_of_year')) {
            Schema::connection('sqlite')->create('life_number_of_year', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create personal_quotes table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('personal_quotes')) {
            Schema::connection('sqlite')->create('personal_quotes', function ($table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('quote_type_id');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->date('dob')->nullable();
                $table->string('source')->nullable();
                $table->string('device')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->timestamps();
            });
        }

        // Create life_quote_request table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('life_quote_request')) {
            Schema::connection('sqlite')->create('life_quote_request', function ($table) {
                $table->id();
                $table->unsignedBigInteger('personal_quote_id');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->decimal('height', 8, 2)->nullable();
                $table->decimal('weight', 8, 2)->nullable();
                $table->decimal('bmi', 8, 2)->nullable();
                $table->integer('age')->nullable();
                $table->decimal('sum_insured_value', 15, 2)->nullable();
                $table->unsignedBigInteger('sum_insured_currency_id')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->unsignedBigInteger('marital_status_id')->nullable();
                $table->unsignedBigInteger('purpose_of_insurance_id')->nullable();
                $table->unsignedBigInteger('number_of_years_id')->nullable();
                $table->boolean('is_smoker')->default(0);
                $table->string('gender')->nullable();
                $table->text('others_info')->nullable();
                $table->string('uuid')->nullable();
                $table->string('lang')->nullable();
                $table->timestamps();
            });
        }

        // Create application_storage table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('application_storage')) {
            Schema::connection('sqlite')->create('application_storage', function ($table) {
                $table->id();
                $table->string('key_name')->unique();
                $table->text('value')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create teams table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('teams')) {
            Schema::connection('sqlite')->create('teams', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Create user_team table if it doesn't exist (pivot table)
        if (! Schema::connection('sqlite')->hasTable('user_team')) {
            Schema::connection('sqlite')->create('user_team', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('team_id');
                $table->timestamps();
            });
        }

        // Create payments table if it doesn't exist (minimal structure)
        if (! Schema::connection('sqlite')->hasTable('payments')) {
            Schema::connection('sqlite')->create('payments', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->morphs('paymentable');
                $table->timestamps();
            });
        }

        // Create lookups table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('lookups')) {
            Schema::connection('sqlite')->create('lookups', function ($table) {
                $table->id();
                $table->string('key')->nullable();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create policy_issuance_status table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('policy_issuance_status')) {
            Schema::connection('sqlite')->create('policy_issuance_status', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create activities table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('activities')) {
            Schema::connection('sqlite')->create('activities', function ($table) {
                $table->id();
                $table->string('activity_type')->nullable();
                $table->integer('reminders_sent')->default(0);
                $table->integer('status')->default(0);
                $table->unsignedBigInteger('assignee_id')->nullable();
                $table->timestamps();
            });
        }

        // Create insurance_provider table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('insurance_provider')) {
            Schema::connection('sqlite')->create('insurance_provider', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->unsignedBigInteger('payment_gateway_id')->nullable();
                $table->timestamps();
            });
        }

        // Create car_plan table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('car_plan')) {
            Schema::connection('sqlite')->create('car_plan', function ($table) {
                $table->id();
                $table->unsignedBigInteger('insurance_provider_id');
                $table->string('plan_name');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create car_quote_request table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('car_quote_request')) {
            Schema::connection('sqlite')->create('car_quote_request', function ($table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->string('code')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->date('dob')->nullable();
                $table->string('registration_type')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->boolean('policy_issuance_automation_enabled')->default(false);
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->timestamps();
            });
        }

        // Create jobs table if it doesn't exist (for database queue driver)
        if (! Schema::connection('sqlite')->hasTable('jobs')) {
            Schema::connection('sqlite')->create('jobs', function ($table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }
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
