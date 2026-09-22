<?php

namespace App\Services;

use App\Enums\GoalStatus;
use App\Enums\TransactionType;
use App\Repositories\Contracts\GoalRepository;
use App\Repositories\Contracts\TransactionRepository;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Goal CRUD plus the derived progress figures (saved, remaining, percent, status).
 */
class GoalService
{
    public function __construct(
        private GoalRepository $goals,
        private TransactionRepository $transactions,
    ) {}

    /**
     * Every goal with its progress summary, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function all(?GoalStatus $status = null): array
    {
        $transactionsByGoal = $this->transactionsByGoal();

        $summaries = array_map(
            fn (array $goal): array => $this->summarize($goal, $transactionsByGoal[$goal['id']] ?? []),
            $this->goals->all(),
        );

        if ($status !== null) {
            $summaries = array_values(array_filter(
                $summaries,
                fn (array $goal): bool => $goal['status'] === $status->value,
            ));
        }

        usort($summaries, fn (array $a, array $b): int => [$b['created_at'], $b['id']] <=> [$a['created_at'], $a['id']]);

        return $summaries;
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrFail(int $id): array
    {
        $goal = $this->goals->find($id) ?? throw new NotFoundHttpException("Goal [{$id}] not found.");

        return $this->summarize($goal, $this->transactions->forGoal($id));
    }

    public function exists(int $id): bool
    {
        return $this->goals->find($id) !== null;
    }

    /**
     * @param  array{name: string, description?: string|null, target_amount: float|int|string, target_date?: string|null}  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $attributes = $this->attributes($data) + ['description' => null, 'target_date' => null];

        return $this->summarize($this->goals->create($attributes), []);
    }

    /**
     * @param  array{name?: string, description?: string|null, target_amount?: float|int|string, target_date?: string|null}  $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        $this->findOrFail($id);
        $this->goals->update($id, $this->attributes($data));

        return $this->findOrFail($id);
    }

    /**
     * Delete the goal together with its transactions.
     */
    public function delete(int $id): void
    {
        $this->findOrFail($id);
        $this->transactions->deleteForGoal($id);
        $this->goals->delete($id);
    }

    /**
     * Current balance of a goal, optionally ignoring one transaction (used when editing it).
     */
    public function balanceInCents(int $goalId, ?int $exceptTransactionId = null): int
    {
        $balance = 0;

        foreach ($this->transactions->forGoal($goalId) as $transaction) {
            if ($transaction['id'] !== $exceptTransactionId) {
                $balance += $this->signedCents($transaction);
            }
        }

        return $balance;
    }

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    public function transactionsByGoal(): array
    {
        $grouped = [];

        foreach ($this->transactions->all() as $transaction) {
            $grouped[$transaction['goal_id']][] = $transaction;
        }

        return $grouped;
    }

    /**
     * Attach progress figures to a stored goal record.
     *
     * @param  array<string, mixed>  $goal
     * @param  list<array<string, mixed>>  $transactions
     * @return array<string, mixed>
     */
    public function summarize(array $goal, array $transactions): array
    {
        usort($transactions, fn (array $a, array $b): int => [$a['date'], $a['id']] <=> [$b['date'], $b['id']]);

        $targetCents = Money::toCents($goal['target_amount']);
        $savedCents = 0;
        $completedAt = null;

        foreach ($transactions as $transaction) {
            $savedCents += $this->signedCents($transaction);

            if ($savedCents >= $targetCents) {
                $completedAt ??= $transaction['date'];
            } else {
                $completedAt = null;
            }
        }

        $isCompleted = $targetCents > 0 && $savedCents >= $targetCents;
        $daysLeft = $goal['target_date'] !== null
            ? (int) Carbon::today()->diffInDays(Carbon::parse($goal['target_date']), false)
            : null;

        return $goal + [
            'saved_amount' => Money::fromCents($savedCents),
            'remaining_amount' => Money::fromCents(max(0, $targetCents - $savedCents)),
            'progress' => $targetCents > 0 ? min(100, round($savedCents / $targetCents * 100, 1)) : 0,
            'status' => ($isCompleted ? GoalStatus::Completed : GoalStatus::Active)->value,
            'completed_at' => $isCompleted ? $completedAt : null,
            'transactions_count' => count($transactions),
            'last_activity_at' => $transactions === [] ? null : end($transactions)['date'],
            'days_left' => $daysLeft,
            'is_overdue' => ! $isCompleted && $daysLeft !== null && $daysLeft < 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    public function signedCents(array $transaction): int
    {
        return Money::toCents(
            TransactionType::from($transaction['type'])->signedAmount((float) $transaction['amount']),
        );
    }

    /**
     * Normalise validated input into the stored record shape.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $attributes = array_intersect_key($data, array_flip(['name', 'description', 'target_amount', 'target_date']));

        if (array_key_exists('target_amount', $attributes)) {
            $attributes['target_amount'] = round((float) $attributes['target_amount'], 2);
        }

        return $attributes;
    }
}
