<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Repositories\Contracts\GoalRepository;
use App\Repositories\Contracts\TransactionRepository;
use App\Support\Money;
use App\Support\TransactionFilter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Saving transactions (deposits and withdrawals) against goals.
 */
class TransactionService
{
    public function __construct(
        private TransactionRepository $transactions,
        private GoalRepository $goals,
        private GoalService $goalService,
    ) {}

    /**
     * Matching transactions, newest first, each with its goal name attached.
     *
     * @return list<array<string, mixed>>
     */
    public function list(TransactionFilter $filter = new TransactionFilter): array
    {
        return $this->withGoalNames($this->sortNewestFirst($filter->apply($this->transactions->all())));
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrFail(int $id): array
    {
        $transaction = $this->transactions->find($id)
            ?? throw new NotFoundHttpException("Transaction [{$id}] not found.");

        return $this->withGoalNames([$transaction])[0];
    }

    /**
     * @param  array{goal_id: int|string, type: string, amount: float|int|string, date: string, note?: string|null}  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $attributes = $this->attributes($data) + ['note' => null];

        $this->ensureWithdrawalIsCovered($attributes);

        return $this->findOrFail($this->transactions->create($attributes)['id']);
    }

    /**
     * @param  array{goal_id?: int|string, type?: string, amount?: float|int|string, date?: string, note?: string|null}  $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        $current = $this->findOrFail($id);
        $attributes = $this->attributes($data);

        $this->ensureWithdrawalIsCovered(array_merge($current, $attributes), $id);
        $this->transactions->update($id, $attributes);

        return $this->findOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id);
        $this->transactions->delete($id);
    }

    /**
     * @param  list<array<string, mixed>>  $transactions
     * @return list<array<string, mixed>>
     */
    public function sortNewestFirst(array $transactions): array
    {
        usort($transactions, fn (array $a, array $b): int => [$b['date'], $b['id']] <=> [$a['date'], $a['id']]);

        return $transactions;
    }

    /**
     * @param  list<array<string, mixed>>  $transactions
     * @return list<array<string, mixed>>
     */
    public function withGoalNames(array $transactions): array
    {
        $goalNames = array_column($this->goals->all(), 'name', 'id');

        return array_map(
            fn (array $transaction): array => $transaction + ['goal_name' => $goalNames[$transaction['goal_id']] ?? null],
            $transactions,
        );
    }

    /**
     * A withdrawal may not take more out of a goal than has been saved into it.
     *
     * @param  array<string, mixed>  $transaction
     *
     * @throws ValidationException
     */
    private function ensureWithdrawalIsCovered(array $transaction, ?int $exceptTransactionId = null): void
    {
        if ($transaction['type'] !== TransactionType::Withdrawal->value) {
            return;
        }

        $available = $this->goalService->balanceInCents($transaction['goal_id'], $exceptTransactionId);

        if (Money::toCents($transaction['amount']) > $available) {
            throw ValidationException::withMessages([
                'amount' => sprintf('The withdrawal exceeds the saved balance of %.2f.', Money::fromCents(max(0, $available))),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $attributes = array_intersect_key($data, array_flip(['goal_id', 'type', 'amount', 'date', 'note']));

        if (array_key_exists('goal_id', $attributes)) {
            $attributes['goal_id'] = (int) $attributes['goal_id'];
        }

        if (array_key_exists('amount', $attributes)) {
            $attributes['amount'] = round((float) $attributes['amount'], 2);
        }

        return $attributes;
    }
}
