<?php

declare(strict_types=1);

use App\Http\Requests\ClaimComplaintStatusUpdateRequest;
use Illuminate\Support\Facades\Validator;

test('complaint_status_id is required when updating complaint status', function () {
    $request = new ClaimComplaintStatusUpdateRequest;

    $validator = Validator::make(
        [
            'complaint_status_id' => null,
            'complaint_datetime' => now()->format('Y-m-d H:i:s'),
        ],
        $request->rules(),
        $request->messages()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('complaint_status_id'))->toBeTrue();
});
