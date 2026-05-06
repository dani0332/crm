<?php

declare(strict_types=1);

use App\Models\BusinessQuote;
use App\Models\GroupMedicalType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('belongs to group medical type for plan type display', function () {
    $relation = (new BusinessQuote)->groupMedicalType();

    expect($relation)->toBeInstanceOf(BelongsTo::class)
        ->and($relation->getRelated())->toBeInstanceOf(GroupMedicalType::class)
        ->and($relation->getForeignKeyName())->toBe('group_medical_type_id');
});
