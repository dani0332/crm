<?php

use App\Exports\RenewalQuotesExport;
use App\Models\Customer;
use App\Models\HealthQuote;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Build a HealthQuote in memory via factory (no DB hit) pre-loaded with the
 * relations that RenewalQuotesExport::map() needs.
 *
 * @param  array<string,mixed>  $attributes  Quote-level attributes to override.
 * @param  Payment|null  $payment  Pre-loaded payment, or null for no payment.
 * @param  Nationality|null  $nationality  Pre-loaded nationality lookup row.
 * @param  User|null  $previousAdvisor  Pre-loaded previous advisor.
 */
function makeHealthQuoteForExport(
    array $attributes = [],
    ?Payment $payment = null,
    ?Nationality $nationality = null,
    ?User $previousAdvisor = null,
): HealthQuote {
    $quote = HealthQuote::factory()->make(array_merge([
        'previous_quote_policy_number' => null,
        'previous_policy_start_date' => null,
        'previous_policy_expiry_date' => null,
        'previous_quote_policy_premium' => null,
        'previous_quote_policy_commission' => null,
        'pc_qualified' => 0,
        'nationality_id' => null,
        'customer_id' => null,
    ], $attributes));

    $customer = Customer::factory()->make(['pcp_tag' => 0, 'nationality_id' => null]);

    $quote->setRelation('customer', $customer);
    $quote->setRelation('currentlyInsuredWith', null);
    $quote->setRelation('previousAdvisor', $previousAdvisor);
    $quote->setRelation('nationality', $nationality);
    $quote->setRelation('payments', $payment !== null ? new Collection([$payment]) : new Collection);

    return $quote;
}

it('uses previous_quote_policy_commission when set, ignoring the payment commission', function () {
    $quote = makeHealthQuoteForExport(
        attributes: ['previous_quote_policy_commission' => 250.00],
        payment: Payment::factory()->make(['commission' => 999.99]),
    );

    $export = new RenewalQuotesExport(collect([$quote]), 'HEALTH');
    $row = $export->map($quote);

    // Commission is at index 9 (0-based), matching headings position
    expect($row[9])->toBe(250.00);
});

it('falls back to payment commission when previous_quote_policy_commission is null', function () {
    $quote = makeHealthQuoteForExport(
        attributes: ['previous_quote_policy_commission' => null],
        payment: Payment::factory()->make(['commission' => 150.50]),
    );

    $export = new RenewalQuotesExport(collect([$quote]), 'HEALTH');
    $row = $export->map($quote);

    expect($row[9])->toBe(150.50);
});

it('returns N/A for commission when both previous_quote_policy_commission and payment are absent', function () {
    $quote = makeHealthQuoteForExport(
        attributes: ['previous_quote_policy_commission' => null],
        payment: null,
    );

    $export = new RenewalQuotesExport(collect([$quote]), 'HEALTH');
    $row = $export->map($quote);

    expect($row[9])->toBe('N/A');
});

it('outputs the nationality text when the nationality relation is loaded', function () {
    $quote = makeHealthQuoteForExport(
        nationality: Nationality::factory()->make(['text' => 'United Arab Emirates']),
    );

    $export = new RenewalQuotesExport(collect([$quote]), 'HEALTH');
    $row = $export->map($quote);

    // Nationality is at index 13, matching headings position
    expect($row[13])->toBe('United Arab Emirates');
});

it('outputs N/A for nationality when the relation is null', function () {
    $quote = makeHealthQuoteForExport(nationality: null);

    $export = new RenewalQuotesExport(collect([$quote]), 'HEALTH');
    $row = $export->map($quote);

    expect($row[13])->toBe('N/A');
});

it('aligns map() output positions with headings() column order', function () {
    $export = new RenewalQuotesExport(collect(), 'HEALTH');
    $headings = $export->headings();

    $quote = makeHealthQuoteForExport(
        attributes: [
            'previous_quote_policy_premium' => 500.00,
            'previous_quote_policy_commission' => 50.00,
            'pc_qualified' => 1,
        ],
        payment: Payment::factory()->make(['commission' => 999.99]),
        nationality: Nationality::factory()->make(['text' => 'British']),
        previousAdvisor: User::factory()->make(['name' => 'Alice Smith']),
    );

    $row = $export->map($quote);
    $indexed = array_combine($headings, $row);

    expect($indexed['Previous Total Price with VAT'])->toBe(500.00)
        ->and($indexed['Previous Commission'])->toBe(50.00)
        ->and($indexed['Previous advisor'])->toBe('Alice Smith')
        ->and($indexed['Lead Level PC Tag'])->toBe('Yes')
        ->and($indexed['Nationality'])->toBe('British');
});

it('places Previous Commission before Previous advisor in headings', function () {
    $export = new RenewalQuotesExport(collect(), 'HEALTH');
    $headings = $export->headings();

    $commissionIndex = array_search('Previous Commission', $headings);
    $advisorIndex = array_search('Previous advisor', $headings);

    expect($commissionIndex)->toBeLessThan($advisorIndex);
});

it('eager loads customer and payments for non-CAR export types', function () {
    $loadedRelations = [];

    $model = new PersonalQuote;

    $query = mock(Builder::class);
    $query->shouldReceive('getModel')->andReturn($model);
    $query->shouldReceive('with')->once()->withArgs(function (array $relations) use (&$loadedRelations) {
        $loadedRelations = $relations;

        return true;
    })->andReturnSelf();
    $query->shouldReceive('get')->andReturn(new Collection);

    (new RenewalQuotesExport($query, 'HEALTH'))->collection();

    expect($loadedRelations)
        ->toContain('customer')
        ->toContain('payments');
});

it('eager loads customer but not payments for CAR export type', function () {
    $loadedRelations = [];

    $model = new PersonalQuote;

    $query = mock(Builder::class);
    $query->shouldReceive('getModel')->andReturn($model);
    $query->shouldReceive('with')->once()->withArgs(function (array $relations) use (&$loadedRelations) {
        $loadedRelations = $relations;

        return true;
    })->andReturnSelf();
    $query->shouldReceive('get')->andReturn(new Collection);

    (new RenewalQuotesExport($query, 'CAR'))->collection();

    expect($loadedRelations)
        ->toContain('customer')
        ->not->toContain('payments');
});
