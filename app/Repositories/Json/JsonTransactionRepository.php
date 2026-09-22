<?php

namespace App\Repositories\Json;

use App\Repositories\Contracts\TransactionRepository;

class JsonTransactionRepository extends JsonRepository implements TransactionRepository
{
    public function forGoal(int $goalId): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (array $transaction): bool => $transaction['goal_id'] === $goalId,
        ));
    }

    public function deleteForGoal(int $goalId): int
    {
        return $this->deleteWhere(fn (array $transaction): bool => $transaction['goal_id'] === $goalId);
    }
}
