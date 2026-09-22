<?php

namespace App\Services;

use App\Enums\GoalStatus;
use App\Enums\TransactionType;
use App\Repositories\Contracts\TransactionRepository;
use App\Support\Money;
use App\Support\TransactionFilter;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Read-only reporting over goals and transactions.
 */
class ReportService
{
    public function __construct(
        private GoalService $goals,
        private TransactionService $transactionService,
        private TransactionRepository $transactions,
    ) {}

    /**
     * Saving history: every matching transaction plus period totals.
     *
     * @return array{transactions: list<array<string, mixed>>, totals: array<string, float|int>}
     */
    public function history(TransactionFilter $filter): array
    {
        $transactions = $this->transactionService->list($filter);

        return [
            'transactions' => $transactions,
            'totals' => $this->totals($transactions),
        ];
    }

    /**
     * Per-goal summary: activity inside the period alongside all-time progress.
     *
     * @return list<array<string, mixed>>
     */
    public function byGoal(TransactionFilter $filter): array
    {
        $periodTransactions = $filter->apply($this->transactions->all());
        $rows = [];

        foreach ($this->goals->all() as $goal) {
            if ($filter->goalId !== null && $goal['id'] !== $filter->goalId) {
                continue;
            }

            $goalTransactions = array_values(array_filter(
                $periodTransactions,
                fn (array $transaction): bool => $transaction['goal_id'] === $goal['id'],
            ));

            $rows[] = [
                'goal_id' => $goal['id'],
                'name' => $goal['name'],
                'status' => $goal['status'],
                'target_amount' => $goal['target_amount'],
                'saved_amount' => $goal['saved_amount'],
                'remaining_amount' => $goal['remaining_amount'],
                'progress' => $goal['progress'],
                'period' => $this->totals($goalTransactions),
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$b['period']['net'], $a['goal_id']] <=> [$a['period']['net'], $b['goal_id']]);

        return $rows;
    }

    /**
     * Month-by-month totals. Covers the filter's date range when given,
     * otherwise the span between the first and last matching transaction.
     *
     * @return list<array<string, mixed>>
     */
    public function monthly(TransactionFilter $filter): array
    {
        $transactions = $filter->apply($this->transactions->all());
        $dates = array_column($transactions, 'date');

        $from = $filter->from ?? ($dates === [] ? null : min($dates));
        $to = $filter->to ?? ($dates === [] ? null : max($dates));

        if ($from === null || $to === null || $from > $to) {
            return [];
        }

        return $this->buildMonths(
            $transactions,
            Carbon::parse($from)->startOfMonth(),
            Carbon::parse($to)->startOfMonth(),
        );
    }

    /**
     * Monthly totals for a fixed month range (inclusive), across all goals.
     *
     * @return list<array<string, mixed>>
     */
    public function monthlyRange(CarbonInterface $fromMonth, CarbonInterface $toMonth): array
    {
        return $this->buildMonths($this->transactions->all(), $fromMonth, $toMonth);
    }

    /**
     * Completed vs. active goals, with the goals that reached their target inside the period.
     *
     * @return array<string, mixed>
     */
    public function goalStatus(TransactionFilter $filter): array
    {
        $goals = $this->goals->all();

        if ($filter->goalId !== null) {
            $goals = array_values(array_filter($goals, fn (array $goal): bool => $goal['id'] === $filter->goalId));
        }

        $groups = [];

        foreach (GoalStatus::cases() as $status) {
            $matching = array_values(array_filter($goals, fn (array $goal): bool => $goal['status'] === $status->value));

            $groups[$status->value] = [
                'count' => count($matching),
                'target_amount' => $this->sumAmounts($matching, 'target_amount'),
                'saved_amount' => $this->sumAmounts($matching, 'saved_amount'),
            ];
        }

        $completedInPeriod = array_values(array_filter($goals, fn (array $goal): bool => $goal['completed_at'] !== null
            && ($filter->from === null || $goal['completed_at'] >= $filter->from)
            && ($filter->to === null || $goal['completed_at'] <= $filter->to)));

        return [
            'active' => $groups[GoalStatus::Active->value],
            'completed' => $groups[GoalStatus::Completed->value],
            'completion_rate' => $goals === [] ? 0 : round($groups[GoalStatus::Completed->value]['count'] / count($goals) * 100, 1),
            'completed_in_period' => array_map(fn (array $goal): array => [
                'id' => $goal['id'],
                'name' => $goal['name'],
                'target_amount' => $goal['target_amount'],
                'completed_at' => $goal['completed_at'],
            ], $completedInPeriod),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $transactions
     * @return array{deposits: float, withdrawals: float, net: float, count: int}
     */
    private function totals(array $transactions): array
    {
        $depositCents = 0;
        $withdrawalCents = 0;

        foreach ($transactions as $transaction) {
            if ($transaction['type'] === TransactionType::Deposit->value) {
                $depositCents += Money::toCents($transaction['amount']);
            } else {
                $withdrawalCents += Money::toCents($transaction['amount']);
            }
        }

        return [
            'deposits' => Money::fromCents($depositCents),
            'withdrawals' => Money::fromCents($withdrawalCents),
            'net' => Money::fromCents($depositCents - $withdrawalCents),
            'count' => count($transactions),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $transactions
     * @return list<array{month: string, deposits: float, withdrawals: float, net: float, count: int}>
     */
    private function buildMonths(array $transactions, CarbonInterface $fromMonth, CarbonInterface $toMonth): array
    {
        $byMonth = [];

        foreach ($transactions as $transaction) {
            $byMonth[substr($transaction['date'], 0, 7)][] = $transaction;
        }

        $months = [];

        for ($month = $fromMonth->copy(); $month->lte($toMonth); $month = $month->copy()->addMonth()) {
            $key = $month->format('Y-m');
            $months[] = ['month' => $key] + $this->totals($byMonth[$key] ?? []);
        }

        return $months;
    }

    /**
     * @param  list<array<string, mixed>>  $goals
     */
    private function sumAmounts(array $goals, string $key): float
    {
        return Money::fromCents(array_sum(array_map(fn (array $goal): int => Money::toCents($goal[$key]), $goals)));
    }
}
