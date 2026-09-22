<?php

namespace App\Services;

use App\Enums\GoalStatus;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * Aggregates the headline figures, recent activity and chart data for the dashboard.
 */
class DashboardService
{
    public function __construct(
        private GoalService $goals,
        private TransactionService $transactions,
        private ReportService $reports,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(int $recentLimit, int $chartMonths): array
    {
        $goals = $this->goals->all();

        $totalSavedCents = array_sum(array_map(fn (array $goal): int => Money::toCents($goal['saved_amount']), $goals));
        $totalTargetCents = array_sum(array_map(fn (array $goal): int => Money::toCents($goal['target_amount']), $goals));
        $remainingCents = array_sum(array_map(fn (array $goal): int => Money::toCents($goal['remaining_amount']), $goals));
        $completedCount = count(array_filter($goals, fn (array $goal): bool => $goal['status'] === GoalStatus::Completed->value));

        $chartStart = Carbon::today()->startOfMonth()->subMonths($chartMonths - 1);

        return [
            'totals' => [
                'total_saved' => Money::fromCents($totalSavedCents),
                'total_target' => Money::fromCents($totalTargetCents),
                'remaining' => Money::fromCents($remainingCents),
                'overall_progress' => $totalTargetCents > 0 ? min(100, round($totalSavedCents / $totalTargetCents * 100, 1)) : 0,
                'goals_count' => count($goals),
                'active_goals' => count($goals) - $completedCount,
                'completed_goals' => $completedCount,
            ],
            'recent_activities' => array_slice($this->transactions->list(), 0, $recentLimit),
            'goal_progress' => array_map(fn (array $goal): array => [
                'id' => $goal['id'],
                'name' => $goal['name'],
                'saved_amount' => $goal['saved_amount'],
                'target_amount' => $goal['target_amount'],
                'progress' => $goal['progress'],
                'status' => $goal['status'],
            ], $goals),
            'monthly_savings' => $this->reports->monthlyRange(
                $chartStart,
                Carbon::today()->startOfMonth(),
            ),
        ];
    }
}
