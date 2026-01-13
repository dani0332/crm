<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\DB;

class TestSchemaCreator
{
    /**
     * Configure database connections to use SQLite for testing.
     * This ensures both 'sqlite' and 'mysql' connections point to the same in-memory database,
     * which is necessary for models that explicitly use the 'mysql' connection.
     */
    private static function configureTestDatabaseConnections(): void
    {
        DB::setDefaultConnection('sqlite');
        
        $sqliteConfig = config('database.connections.sqlite', ['database' => ':memory:', 'prefix' => '']);
        config([
            'database.default' => 'sqlite',
            'database.connections.mysql' => array_merge($sqliteConfig, ['driver' => 'sqlite']),
        ]);
        
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    /**
     * Create minimal required tables for LifeQuote tests.
     */
    public static function createMinimalSchema(): void
    {
        self::configureTestDatabaseConnections();
        
        $sqliteSchema = DB::connection('sqlite')->getSchemaBuilder();
        $mysqlSchema = DB::connection('mysql')->getSchemaBuilder();
        
        // Helper to create table on both connections since some models use 'mysql' explicitly
        $createOnBoth = function ($tableName, $callback) use ($sqliteSchema, $mysqlSchema) {
            if (! $sqliteSchema->hasTable($tableName)) {
                $sqliteSchema->create($tableName, $callback);
            }
            if (! $mysqlSchema->hasTable($tableName)) {
                $mysqlSchema->create($tableName, $callback);
            }
        };
        
        $schema = $sqliteSchema; // Keep for backward compatibility

        // Create audits table if it doesn't exist (for Laravel Auditing)
        if (! $schema->hasTable('audits')) {
            $schema->create('audits', function ($table) {
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
        if (! $schema->hasTable('users')) {
            $schema->create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('remember_token')->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->timestamps();
            });
        } elseif (! $schema->hasColumn('users', 'is_active')) {
            // Add is_active column if table exists but column doesn't
            $schema->table('users', function ($table) {
                $table->tinyInteger('is_active')->default(1)->after('remember_token');
            });
        }

        // Create roles table if it doesn't exist (for Spatie permissions)
        if (! $schema->hasTable('roles')) {
            $schema->create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // Create model_has_roles table if it doesn't exist
        if (! $schema->hasTable('model_has_roles')) {
            $schema->create('model_has_roles', function ($table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            });
        }

        // Create permissions table if it doesn't exist (for Spatie permissions)
        if (! $schema->hasTable('permissions')) {
            $schema->create('permissions', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // Create model_has_permissions table if it doesn't exist
        if (! $schema->hasTable('model_has_permissions')) {
            $schema->create('model_has_permissions', function ($table) {
                $table->id();
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            });
        }

        // Create role_has_permissions table if it doesn't exist (pivot table for Spatie permissions)
        if (! $schema->hasTable('role_has_permissions')) {
            $schema->create('role_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        // Create nationality table if it doesn't exist
        if (! $schema->hasTable('nationality')) {
            $schema->create('nationality', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create currency_type table if it doesn't exist
        if (! $schema->hasTable('currency_type')) {
            $schema->create('currency_type', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create marital_status table if it doesn't exist
        if (! $schema->hasTable('marital_status')) {
            $schema->create('marital_status', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create life_insurance_purpose table if it doesn't exist
        if (! $schema->hasTable('life_insurance_purpose')) {
            $schema->create('life_insurance_purpose', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create life_number_of_year table if it doesn't exist
        if (! $schema->hasTable('life_number_of_year')) {
            $schema->create('life_number_of_year', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create personal_quotes table if it doesn't exist
        if (! $schema->hasTable('personal_quotes')) {
            $schema->create('personal_quotes', function ($table) {
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
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->string('insurer_aml_status')->nullable();
                $table->timestamps();
            });
        } elseif ($schema->hasTable('personal_quotes')) {
            // Add missing columns if table exists but columns don't
            if (! $schema->hasColumn('personal_quotes', 'quote_status_id')) {
                $schema->table('personal_quotes', function ($table) {
                    $table->unsignedBigInteger('quote_status_id')->nullable()->after('updated_by_id');
                });
            }
            if (! $schema->hasColumn('personal_quotes', 'insurer_aml_status')) {
                $schema->table('personal_quotes', function ($table) {
                    $table->string('insurer_aml_status')->nullable()->after('quote_status_id');
                });
            }
        }

        // Create life_quote_request table if it doesn't exist
        if (! $schema->hasTable('life_quote_request')) {
            $schema->create('life_quote_request', function ($table) {
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

        // Create quote_type table if it doesn't exist
        if (! $schema->hasTable('quote_type')) {
            $schema->create('quote_type', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('short_code')->nullable();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            });
        }

        // Create quote_status table if it doesn't exist
        if (! $schema->hasTable('quote_status')) {
            $schema->create('quote_status', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->string('text_ar')->nullable();
                $table->boolean('is_active')->default(1);
                $table->integer('sort_order')->nullable();
                $table->boolean('is_deleted')->default(0);
                $table->string('uuid')->nullable();
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create quote_status_map table if it doesn't exist
        if (! $schema->hasTable('quote_status_map')) {
            $schema->create('quote_status_map', function ($table) {
                $table->id();
                $table->unsignedBigInteger('quote_status_id');
                $table->unsignedBigInteger('quote_type_id');
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            });
        }

        // Create car_plan table if it doesn't exist
        if (! $schema->hasTable('car_plan')) {
            $schema->create('car_plan', function ($table) {
                $table->id();
                $table->string('text')->nullable();
                $table->timestamps();
            });
        }

        // Create car_plan_coverage table if it doesn't exist
        if (! $schema->hasTable('car_plan_coverage')) {
            $schema->create('car_plan_coverage', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->string('text_ar')->nullable();
                $table->string('value')->nullable();
                $table->string('value_ar')->nullable();
                $table->string('type')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->timestamps();
            });
        }

        // Create quote_batches table if it doesn't exist
        if (! $schema->hasTable('quote_batches')) {
            $schema->create('quote_batches', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamps();
            });
        }

        // Create renewal_batches table if it doesn't exist
        if (! $schema->hasTable('renewal_batches')) {
            $schema->create('renewal_batches', function ($table) {
                $table->id();
                $table->string('name');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('month')->nullable();
                $table->integer('year')->nullable();
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->timestamps();
            });
        }

        // Create application_storage table if it doesn't exist
        if (! $schema->hasTable('application_storage')) {
            $schema->create('application_storage', function ($table) {
                $table->id();
                $table->string('key_name')->unique();
                $table->text('value')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create teams table if it doesn't exist
        if (! $schema->hasTable('teams')) {
            $schema->create('teams', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Create user_team table if it doesn't exist (pivot table)
        if (! $schema->hasTable('user_team')) {
            $schema->create('user_team', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('team_id');
                $table->unsignedBigInteger('manager_id')->nullable();
                $table->timestamps();
            });
        } elseif ($schema->hasTable('user_team')) {
            // Add missing columns if table exists but columns don't
            if (! $schema->hasColumn('user_team', 'manager_id')) {
                $schema->table('user_team', function ($table) {
                    $table->unsignedBigInteger('manager_id')->nullable()->after('team_id');
                });
            }
        }

        // Create user_products table if it doesn't exist (pivot table)
        if (! $schema->hasTable('user_products')) {
            $schema->create('user_products', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('product_id');
                $table->timestamps();
            });
        }

        // Create tiers table if it doesn't exist
        if (! $schema->hasTable('tiers')) {
            $schema->create('tiers', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->decimal('min_price', 15, 2)->nullable();
                $table->decimal('max_price', 15, 2)->nullable();
                $table->decimal('cost_per_lead', 15, 2)->nullable();
                $table->boolean('can_handle_null_value')->default(0);
                $table->boolean('can_handle_ecommerce')->default(0);
                $table->boolean('is_active')->default(1);
                $table->boolean('can_handle_tpl')->default(0);
                $table->boolean('is_tpl_renewals')->default(0);
                $table->timestamps();
            });
        }

        // Create payment_status table if it doesn't exist
        if (! $schema->hasTable('payment_status')) {
            $schema->create('payment_status', function ($table) {
                $table->id();
                $table->string('text')->nullable();
                $table->string('text_ar')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create payments table if it doesn't exist (minimal structure)
        if (! $schema->hasTable('payments')) {
            $schema->create('payments', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->morphs('paymentable');
                $table->timestamps();
            });
        }

        // Create life_insurance_tenure table if it doesn't exist
        if (! $schema->hasTable('life_insurance_tenure')) {
            $schema->create('life_insurance_tenure', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->string('text_ar')->nullable();
                $table->boolean('is_active')->default(1);
                $table->boolean('is_deleted')->default(0);
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            });
        }

        // Create lookups table if it doesn't exist
        if (! $schema->hasTable('lookups')) {
            $schema->create('lookups', function ($table) {
                $table->id();
                $table->string('key')->nullable();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create policy_issuance_status table if it doesn't exist
        if (! $schema->hasTable('policy_issuance_status')) {
            $schema->create('policy_issuance_status', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create activities table if it doesn't exist
        if (! $schema->hasTable('activities')) {
            $schema->create('activities', function ($table) {
                $table->id();
                $table->string('activity_type')->nullable();
                $table->integer('reminders_sent')->default(0);
                $table->integer('status')->default(0);
                $table->unsignedBigInteger('assignee_id')->nullable();
                $table->timestamps();
            });
        }

        // Create insured table if it doesn't exist
        if (! $schema->hasTable('insured')) {
            $schema->create('insured', function ($table) {
                $table->id();
                $table->string('insurable_type')->nullable(); // Polymorphic relationship
                $table->unsignedBigInteger('insurable_id')->nullable(); // Polymorphic relationship
                $table->string('customer_type')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->date('dob')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->string('gender')->nullable();
                $table->string('id_type')->nullable();
                $table->string('id_number')->nullable();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('customer_details_id')->nullable();
                $table->unsignedBigInteger('emirate_of_registration_id')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create insured_kyc table if it doesn't exist
        if (! $schema->hasTable('insured_kyc')) {
            $schema->create('insured_kyc', function ($table) {
                $table->id();
                $table->unsignedBigInteger('insured_id');
                $table->unsignedBigInteger('customer_details_id')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('mobile_no')->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->string('legal_structure')->nullable();
                $table->string('country_of_corporation')->nullable();
                $table->string('registered_address')->nullable();
                $table->string('communication_address')->nullable();
                $table->string('country_of_residence')->nullable();
                $table->string('place_of_birth')->nullable();
                $table->string('residential_status')->nullable();
                $table->string('residential_address')->nullable();
                $table->string('id_type')->nullable();
                $table->string('id_number')->nullable();
                $table->date('id_issuance_date')->nullable();
                $table->date('id_expiry_date')->nullable();
                $table->string('issuance_place')->nullable();
                $table->string('id_issuance_authority')->nullable();
                $table->string('source_of_income')->nullable();
                $table->string('employer_company_name')->nullable();
                $table->string('job_title')->nullable();
                $table->string('employment_sector')->nullable();
                $table->string('position_in_company')->nullable();
                $table->string('trade_license_no')->nullable();
                $table->boolean('pep')->nullable();
                $table->boolean('financial_sanctions')->nullable();
                $table->boolean('dual_nationality')->nullable();
                $table->string('customer_tenure')->nullable();
                $table->string('transaction_volume')->nullable();
                $table->string('transaction_activities')->nullable();
                $table->string('transaction_pattern')->nullable();
                $table->string('premium_tenure')->nullable();
                $table->string('mode_of_contact')->nullable();
                $table->string('mode_of_delivery')->nullable();
                $table->string('risk_score')->nullable();
                $table->boolean('in_sanction_list')->nullable();
                $table->boolean('deal_sanction_list')->nullable();
                $table->boolean('is_operation_high_risk')->nullable();
                $table->boolean('is_partner')->nullable();
                $table->boolean('in_adverse_media')->nullable();
                $table->boolean('is_owner_pep')->nullable();
                $table->boolean('is_controlling_pep')->nullable();
                $table->boolean('is_sanction_match')->nullable();
                $table->boolean('in_fatf')->nullable();
                $table->string('industry_type')->nullable();
                $table->string('manager_name')->nullable();
                $table->string('manager_nationality')->nullable();
                $table->date('manager_dob')->nullable();
                $table->string('manager_position')->nullable();
                $table->boolean('is_owner_high_risk')->nullable();
                $table->timestamps();
            });
        }

        // Create health_cover_for table if it doesn't exist
        if (! $schema->hasTable('health_cover_for')) {
            $schema->create('health_cover_for', function ($table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create health_lead_type table if it doesn't exist
        if (! $schema->hasTable('health_lead_type')) {
            $schema->create('health_lead_type', function ($table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create emirates table if it doesn't exist
        if (! $schema->hasTable('emirates')) {
            $schema->create('emirates', function ($table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create salary_band table if it doesn't exist
        if (! $schema->hasTable('salary_band')) {
            $schema->create('salary_band', function ($table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            });
        }

        // Create member_category table if it doesn't exist
        if (! $schema->hasTable('member_category')) {
            $schema->create('member_category', function ($table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            });
        }

        // Create insurance_provider table if it doesn't exist (on both connections since model uses 'mysql')
        $createOnBoth('insurance_provider', function ($table) {
            $table->id();
            $table->string('text')->nullable();
            $table->string('text_lms')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_deleted')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        // Create travel_quote_request table if it doesn't exist
        if (! $schema->hasTable('travel_quote_request')) {
            $schema->create('travel_quote_request', function ($table) {
                $table->id();
                $table->string('uuid')->nullable();
                $table->string('code')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('direction_code')->nullable();
                $table->string('coverage_code')->nullable();
                $table->string('policy_number')->nullable();
                $table->string('source')->nullable();
                $table->unsignedBigInteger('sub_source_id')->nullable();
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->unsignedBigInteger('previous_quote_id')->nullable();
                $table->date('policy_expiry_date')->nullable();
                $table->string('renewal_batch')->nullable();
                $table->string('renewal_import_code')->nullable();
                $table->string('previous_quote_policy_number')->nullable();
                $table->decimal('previous_quote_policy_premium', 10, 2)->nullable();
                $table->string('device')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->date('policy_start_date')->nullable();
                $table->date('policy_issuance_date')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('parent_duplicate_quote_id')->nullable();
                $table->boolean('is_ecommerce')->default(0);
                $table->date('policy_booking_date')->nullable();
                $table->string('insurer_quote_number')->nullable();
                $table->unsignedBigInteger('policy_issuance_status_id')->nullable();
                $table->string('policy_issuance_status_other')->nullable();
                $table->string('sic_advisor_requested')->nullable();
                $table->string('aml_status')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->string('insurer_aml_status')->nullable();
                $table->unsignedBigInteger('renewal_batch_id')->nullable();
                $table->date('previous_policy_expiry_date')->nullable();
                $table->date('dob')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->timestamp('transaction_approved_at')->nullable();
                $table->string('assignment_type')->nullable();
                $table->string('gender')->nullable();
                $table->decimal('premium', 10, 2)->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->unsignedBigInteger('currently_located_in_id')->nullable();
                $table->unsignedBigInteger('api_issuance_status_id')->nullable();
                $table->unsignedBigInteger('insurer_api_status_id')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('days_cover_for')->nullable();
                $table->string('lead_assignment_trigger')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->boolean('is_branch_applicable')->default(0);
                $table->timestamps();
            });
        }

        // Create insurance_provider_quote_type pivot table if it doesn't exist
        if (! $schema->hasTable('insurance_provider_quote_type')) {
            $schema->create('insurance_provider_quote_type', function ($table) {
                $table->id();
                $table->unsignedBigInteger('insurance_provider_id');
                $table->unsignedBigInteger('quote_type_id');
                $table->timestamps();
            });
        }

        // Create travel_plan table if it doesn't exist
        if (! $schema->hasTable('travel_plan')) {
            $schema->create('travel_plan', function ($table) {
                $table->id();
                $table->string('text')->nullable();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('provider_id')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create health_plan_type table if it doesn't exist
        if (! $schema->hasTable('health_plan_type')) {
            $schema->create('health_plan_type', function ($table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create health_quote_request table if it doesn't exist
        if (! $schema->hasTable('health_quote_request')) {
            $schema->create('health_quote_request', function ($table) {
                $table->id();
                $table->string('uuid')->nullable();
                $table->string('code')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('source')->nullable();
                $table->unsignedBigInteger('sub_source_id')->nullable();
                $table->string('health_team_type')->nullable();
                $table->decimal('premium', 10, 2)->nullable();
                $table->string('policy_number')->nullable();
                $table->unsignedBigInteger('support_user_id')->nullable();
                $table->unsignedBigInteger('marital_status_id')->nullable();
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->unsignedBigInteger('lead_type_id')->nullable();
                $table->unsignedBigInteger('previous_quote_id')->nullable();
                $table->unsignedBigInteger('salary_band_id')->nullable();
                $table->unsignedBigInteger('member_category_id')->nullable();
                $table->date('policy_expiry_date')->nullable();
                $table->string('renewal_batch')->nullable();
                $table->string('renewal_import_code')->nullable();
                $table->string('previous_quote_policy_number')->nullable();
                $table->decimal('previous_quote_policy_premium', 10, 2)->nullable();
                $table->string('device')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->date('policy_start_date')->nullable();
                $table->date('policy_issuance_date')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('currently_insured_with_id')->nullable();
                $table->unsignedBigInteger('parent_duplicate_quote_id')->nullable();
                $table->boolean('is_ecommerce')->default(0);
                $table->decimal('price_starting_from', 10, 2)->nullable();
                $table->date('policy_booking_date')->nullable();
                $table->string('insurer_quote_number')->nullable();
                $table->unsignedBigInteger('policy_issuance_status_id')->nullable();
                $table->string('policy_issuance_status_other')->nullable();
                $table->timestamp('stale_at')->nullable();
                $table->string('sic_advisor_requested')->nullable();
                $table->string('aml_status')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->string('insurer_aml_status')->nullable();
                $table->unsignedBigInteger('renewal_batch_id')->nullable();
                $table->date('previous_policy_expiry_date')->nullable();
                $table->date('dob')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->timestamp('transaction_approved_at')->nullable();
                $table->string('assignment_type')->nullable();
                $table->string('gender')->nullable();
                $table->unsignedBigInteger('emirate_of_your_visa_id')->nullable();
                $table->timestamp('pec_marked_at')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->boolean('is_branch_applicable')->default(0);
                $table->timestamps();
            });
        }

        // Create lead_allocation table if it doesn't exist
        if (! $schema->hasTable('lead_allocation')) {
            $schema->create('lead_allocation', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->integer('auto_assignment_count')->default(0);
                $table->integer('manual_assignment_count')->default(0);
                $table->integer('max_capacity')->default(0);
                $table->timestamps();
            });
        }

        // Create health_quote_request_detail table if it doesn't exist
        if (! $schema->hasTable('health_quote_request_detail')) {
            $schema->create('health_quote_request_detail', function ($table) {
                $table->id();
                $table->unsignedBigInteger('health_quote_request_id');
                $table->timestamp('advisor_assigned_date')->nullable();
                $table->unsignedBigInteger('lost_reason_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }
}
