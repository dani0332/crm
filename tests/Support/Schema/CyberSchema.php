<?php

namespace Tests\Support\Schema;

use Illuminate\Database\Schema\Blueprint;

class CyberSchema
{
    public function register(): void
    {
        $this->ensureTables();
        $this->ensurePersonalQuoteColumns();
        $this->ensurePaymentColumns();
        $this->ensurePaymentSplitColumns();
    }

    private function ensureTables(): void
    {
        SchemaUtils::ensureTables([
            'cyber_quote_request' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('personal_quote_id');
                $table->unsignedBigInteger('emirate_of_registration_id')->nullable();
                $table->unsignedBigInteger('coverage_id')->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensurePersonalQuoteColumns(): void
    {
        $columns = [
            'plan_id' => fn (Blueprint $table) => $table->unsignedBigInteger('plan_id')->nullable(),
            'customer_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_id')->nullable(),
            'nationality_id' => fn (Blueprint $table) => $table->unsignedBigInteger('nationality_id')->nullable(),
            'policy_start_date' => fn (Blueprint $table) => $table->date('policy_start_date')->nullable(),
            'policy_expiry_date' => fn (Blueprint $table) => $table->date('policy_expiry_date')->nullable(),
            'policy_issuance_date' => fn (Blueprint $table) => $table->date('policy_issuance_date')->nullable(),
            'policy_number' => fn (Blueprint $table) => $table->string('policy_number')->nullable(),
            'policy_issuance_status_id' => fn (Blueprint $table) => $table->unsignedBigInteger('policy_issuance_status_id')->nullable(),
            'price_vat_applicable' => fn (Blueprint $table) => $table->decimal('price_vat_applicable', 12, 2)->nullable(),
            'price_with_vat' => fn (Blueprint $table) => $table->decimal('price_with_vat', 12, 2)->nullable(),
            'vat' => fn (Blueprint $table) => $table->decimal('vat', 12, 2)->nullable(),
            'insurer_quote_number' => fn (Blueprint $table) => $table->string('insurer_quote_number')->nullable(),
            'quote_status_id' => fn (Blueprint $table) => $table->unsignedBigInteger('quote_status_id')->nullable(),
            'quote_status_date' => fn (Blueprint $table) => $table->timestamp('quote_status_date')->nullable(),
            'insurer_debit_note_doc_id' => fn (Blueprint $table) => $table->string('insurer_debit_note_doc_id')->nullable(),
            'insurer_tax_invoice_doc_id' => fn (Blueprint $table) => $table->string('insurer_tax_invoice_doc_id')->nullable(),
            'insurer_policy_doc_id' => fn (Blueprint $table) => $table->string('insurer_policy_doc_id')->nullable(),
            'documents' => fn (Blueprint $table) => $table->json('documents')->nullable(),
            'insurer_api_status_id' => fn (Blueprint $table) => $table->unsignedBigInteger('insurer_api_status_id')->nullable(),
            'api_issuance_status_id' => fn (Blueprint $table) => $table->unsignedBigInteger('api_issuance_status_id')->nullable(),
        ];

        foreach ($columns as $column => $callback) {
            SchemaUtils::addColumnIfMissing('personal_quotes', $column, $callback);
        }
    }

    private function ensurePaymentColumns(): void
    {
        $columns = [
            'commission_vat_applicable' => fn (Blueprint $table) => $table->decimal('commission_vat_applicable', 12, 2)->nullable(),
            'commission' => fn (Blueprint $table) => $table->decimal('commission', 12, 2)->nullable(),
            'commission_vat' => fn (Blueprint $table) => $table->decimal('commission_vat', 12, 2)->nullable(),
            'commmission_percentage' => fn (Blueprint $table) => $table->decimal('commmission_percentage', 12, 2)->nullable(),
            'insurer_tax_number' => fn (Blueprint $table) => $table->string('insurer_tax_number')->nullable(),
            'insurer_invoice_date' => fn (Blueprint $table) => $table->date('insurer_invoice_date')->nullable(),
            'insurer_commmission_invoice_number' => fn (Blueprint $table) => $table->string('insurer_commmission_invoice_number')->nullable(),
            'price_vat_applicable' => fn (Blueprint $table) => $table->decimal('price_vat_applicable', 12, 2)->nullable(),
            'price_vat' => fn (Blueprint $table) => $table->decimal('price_vat', 12, 2)->nullable(),
            'send_update_log_id' => fn (Blueprint $table) => $table->unsignedBigInteger('send_update_log_id')->nullable(),
        ];

        foreach ($columns as $column => $callback) {
            SchemaUtils::addColumnIfMissing('payments', $column, $callback);
        }
    }

    private function ensurePaymentSplitColumns(): void
    {
        $columns = [
            'price_vat_applicable' => fn (Blueprint $table) => $table->decimal('price_vat_applicable', 12, 2)->nullable(),
            'price_vat' => fn (Blueprint $table) => $table->decimal('price_vat', 12, 2)->nullable(),
        ];

        foreach ($columns as $column => $callback) {
            SchemaUtils::addColumnIfMissing('payment_splits', $column, $callback);
        }
    }
}
