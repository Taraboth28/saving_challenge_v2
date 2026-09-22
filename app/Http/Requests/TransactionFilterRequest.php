<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Support\TransactionFilter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query-string filters shared by the transaction list and every report.
 */
class TransactionFilterRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'goal_id' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
        ];
    }

    public function filter(): TransactionFilter
    {
        return TransactionFilter::fromArray($this->validated());
    }
}
