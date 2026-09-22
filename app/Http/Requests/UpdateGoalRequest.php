<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'target_amount' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999.99'],
            'target_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ];
    }
}
