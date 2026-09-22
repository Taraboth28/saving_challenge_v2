<?php

namespace App\Rules;

use App\Repositories\Contracts\GoalRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ensures the value is the id of a stored goal (JSON storage has no "exists" rule).
 */
class ExistingGoal implements ValidationRule
{
    public function __construct(private GoalRepository $goals) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || $this->goals->find((int) $value) === null) {
            $fail('The selected goal does not exist.');
        }
    }
}
