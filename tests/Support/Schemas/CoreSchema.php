<?php

namespace Tests\Support\Schemas;

use Illuminate\Database\Schema\Blueprint;

class CoreSchema
{
    public function register(): void
    {
        $this->ensureAuditTables();
        $this->ensureUserAndPermissionTables();
        $this->ensureReferenceTables();
        $this->ensureQuoteTables();
        $this->ensureApplicationStorageTable();
        $this->ensureTeamTables();
        $this->ensurePaymentTables();
        $this->ensureLookupTables();
    }

    private function ensureAuditTables(): void
    {
        SchemaUtils::ensureTables([
            'audits' => function (Blueprint $table) {
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
            },
        ]);
    }

    private function ensureUserAndPermissionTables(): void
    {
        SchemaUtils::ensureTables([
            'users' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('remember_token')->nullable();
                $table->timestamps();
            },
            'roles' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            },
            'model_has_roles' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            },
            'permissions' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            },
            'model_has_permissions' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            },
        ]);

        SchemaUtils::ensureTable('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }

    private function ensureReferenceTables(): void
    {
        SchemaUtils::ensureTables([
            'nationality' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->softDeletes();
                $table->timestamps();
            },
            'currency_type' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'marital_status' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'life_insurance_purpose' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'life_number_of_year' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
        ]);
    }

    private function ensureQuoteTables(): void
    {
        SchemaUtils::ensureTables([
            'personal_quotes' => function (Blueprint $table) {
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
            },
            'life_quote_request' => function (Blueprint $table) {
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
            },
        ]);
    }

    private function ensureApplicationStorageTable(): void
    {
        SchemaUtils::ensureTable('application_storage', function (Blueprint $table) {
            $table->id();
            $table->string('key_name')->unique();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        SchemaUtils::addColumnIfMissing(
            'application_storage',
            'is_active',
            fn (Blueprint $table) => $table->boolean('is_active')->default(1)
        );
    }

    private function ensureTeamTables(): void
    {
        SchemaUtils::ensureTables([
            'teams' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            },
            'user_team' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('team_id');
                $table->timestamps();
            },
        ]);
    }

    private function ensurePaymentTables(): void
    {
        SchemaUtils::ensureTables([
            'payments' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->morphs('paymentable');
                $table->timestamps();
            },
        ]);
    }

    private function ensureLookupTables(): void
    {
        SchemaUtils::ensureTables([
            'lookups' => function (Blueprint $table) {
                $table->id();
                $table->string('key')->nullable();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'policy_issuance_status' => function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'activities' => function (Blueprint $table) {
                $table->id();
                $table->string('activity_type')->nullable();
                $table->integer('reminders_sent')->default(0);
                $table->integer('status')->default(0);
                $table->unsignedBigInteger('assignee_id')->nullable();
                $table->timestamps();
            },
        ]);
    }
}

