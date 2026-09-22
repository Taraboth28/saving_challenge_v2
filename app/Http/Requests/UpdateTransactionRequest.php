<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Repositories\Contracts\GoalRepository;
use App\Rules\ExistingGoal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(GoalRepository $goals): array
    {
        return [
            'goal_id' => ['sometimes', 'required', 'integer', new ExistingGoal($goals)],
            'type' => ['sometimes', 'required', Rule::enum(TransactionType::class)],
            'amount' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999.99'],
            'date' => ['sometimes', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
