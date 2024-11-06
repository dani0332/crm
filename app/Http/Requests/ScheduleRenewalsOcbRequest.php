<?php

namespace App\Http\Requests;

use App\Enums\RolesEnum;
use App\Models\RenewalsBatchEmails;
use App\Services\RenewalsUploadService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleRenewalsOcbRequest extends FormRequest
{
    protected $renewalsUploadService;
    public function __construct(RenewalsUploadService $renewalsUploadService)
    {
        $this->renewalsUploadService = $renewalsUploadService;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->hasAnyRole([RolesEnum::RenewalsManager, RolesEnum::Admin, RolesEnum::Engineering]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $batch = request()->batch;

            $lastScheduleTime = RenewalsBatchEmails::where('batch', $batch)->orderBy('created_at', 'desc')->first();

            if ($lastScheduleTime && Carbon::parse($lastScheduleTime->created_at)->timezone(config('app.timezone'))->diffInMinutes(Carbon::now()) <= 5) {
                $validator->errors()->add('error', 'Batch process is already created, next can be created after 5 minutes');
            }

            $totalLeads = $this->renewalsUploadService->getPendingOcbLeadsTotal($batch);
            if ($totalLeads == 0) {
                $validator->errors()->add('error', 'No leads found for batch - '.$batch);
            }
        });
    }
}
