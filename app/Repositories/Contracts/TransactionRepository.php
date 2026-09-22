<?php

namespace App\Repositories\Contracts;

interface TransactionRepository extends RecordRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forGoal(int $goalId): array;

    /**
     * Delete every transaction belonging to the goal and return how many were removed.
     */
    public function deleteForGoal(int $goalId): int;
}
