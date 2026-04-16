<?php

namespace App\Http\Requests\Api;

use App\Enums\CacheKeyEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClearCacheRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', Rule::enum(CacheKeyEnum::class)],
        ];
    }

    public function getKey()
    {
        return CacheKeyEnum::from($this->key);
    }
}
