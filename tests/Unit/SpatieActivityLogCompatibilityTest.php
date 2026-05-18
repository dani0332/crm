<?php

use App\Models\Customer;
use App\Traits\SpatieActivityLog;
use Spatie\Activitylog\Models\Activity as SpatieActivity;
use Spatie\Activitylog\Traits\LogsActivity;

it('resolves Spatie activitylog v4 LogsActivity trait used by SpatieActivityLog', function () {
    expect(trait_exists(LogsActivity::class))->toBeTrue();

    $traits = class_uses_recursive(SpatieActivityLog::class);
    expect($traits)->toHaveKey(LogsActivity::class);
});

it('models using SpatieActivityLog expose tapActivity for v4', function () {
    expect(method_exists(Customer::class, 'tapActivity'))->toBeTrue();
});

it('allows enriching a Spatie Activity row before save', function () {
    $activity = new SpatieActivity;
    $activity->event = 'updated';

    $model = new class extends Customer
    {
        public function getChanges(): array
        {
            return ['name' => 'new'];
        }

        public function getOriginal($key = null, $default = null): mixed
        {
            if ($key === 'name') {
                return 'old';
            }

            return parent::getOriginal($key, $default);
        }
    };

    $model->tapActivity($activity, 'updated');

    expect($activity->attribute_changes)->not->toBeNull()
        ->and($activity->attribute_changes->get('old'))->toMatchArray(['name' => 'old'])
        ->and($activity->attribute_changes->get('attributes'))->toMatchArray(['name' => 'new']);
});
