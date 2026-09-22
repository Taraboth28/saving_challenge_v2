<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardAndReportApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_returns_totals_recent_activity_and_chart_data(): void
    {
        $laptop = $this->createGoal(['name' => 'Laptop', 'target_amount' => 1000]);
        $bike = $this->createGoal(['name' => 'Bike', 'target_amount' => 200]);
        $this->createTransaction($laptop['id'], ['amount' => 300, 'date' => '2026-05-02']);
        $this->createTransaction($laptop['id'], ['amount' => 100, 'date' => '2026-06-01']);
        $this->createTransaction($bike['id'], ['amount' => 200, 'date' => '2026-06-10']);

        $response = $this->getJson('/api/v1/dashboard')->assertOk();

        $response->assertJsonPath('data.totals.total_saved', 600)
            ->assertJsonPath('data.totals.total_target', 1200)
            ->assertJsonPath('data.totals.remaining', 600)
            ->assertJsonPath('data.totals.overall_progress', 50)
            ->assertJsonPath('data.totals.active_goals', 1)
            ->assertJsonPath('data.totals.completed_goals', 1)
            ->assertJsonPath('data.recent_activities.0.goal_name', 'Bike')
            ->assertJsonCount(2, 'data.goal_progress')
            ->assertJsonCount(6, 'data.monthly_savings')
            ->assertJsonPath('data.monthly_savings.4', ['month' => '2026-05', 'deposits' => 300, 'withdrawals' => 0, 'net' => 300, 'count' => 1])
            ->assertJsonPath('data.monthly_savings.5.net', 300);
    }

    public function test_history_report_filters_by_date_and_goal(): void
    {
        $laptop = $this->createGoal(['name' => 'Laptop']);
        $bike = $this->createGoal(['name' => 'Bike']);
        $this->createTransaction($laptop['id'], ['amount' => 300, 'date' => '2026-04-02']);
        $this->createTransaction($laptop['id'], ['amount' => 50, 'type' => 'withdrawal', 'date' => '2026-05-03']);
        $this->createTransaction($bike['id'], ['amount' => 80, 'date' => '2026-05-20']);

        $this->getJson("/api/v1/reports/history?from=2026-05-01&goal_id={$laptop['id']}")
            ->assertOk()
            ->assertJsonCount(1, 'data.transactions')
            ->assertJsonPath('data.totals', ['deposits' => 0, 'withdrawals' => 50, 'net' => -50, 'count' => 1])
            ->assertJsonPath('filters.goal_id', $laptop['id']);
    }

    public function test_goal_summary_report_shows_period_activity_per_goal(): void
    {
        $laptop = $this->createGoal(['name' => 'Laptop', 'target_amount' => 1000]);
        $bike = $this->createGoal(['name' => 'Bike', 'target_amount' => 100]);
        $this->createTransaction($laptop['id'], ['amount' => 300, 'date' => '2026-04-02']);
        $this->createTransaction($laptop['id'], ['amount' => 200, 'date' => '2026-05-02']);
        $this->createTransaction($bike['id'], ['amount' => 50, 'date' => '2026-05-10']);

        $this->getJson('/api/v1/reports/goals?from=2026-05-01&to=2026-05-31')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Laptop')
            ->assertJsonPath('data.0.period.net', 200)
            ->assertJsonPath('data.0.saved_amount', 500)
            ->assertJsonPath('data.1.period.net', 50);
    }

    public function test_monthly_report_fills_months_without_activity(): void
    {
        $goal = $this->createGoal();
        $this->createTransaction($goal['id'], ['amount' => 100, 'date' => '2026-02-05']);
        $this->createTransaction($goal['id'], ['amount' => 40, 'date' => '2026-04-25']);

        $this->getJson('/api/v1/reports/monthly')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.1', ['month' => '2026-03', 'deposits' => 0, 'withdrawals' => 0, 'net' => 0, 'count' => 0])
            ->assertJsonPath('data.2.net', 40);
    }

    public function test_status_report_compares_completed_and_active_goals(): void
    {
        $done = $this->createGoal(['name' => 'Done', 'target_amount' => 100]);
        $this->createGoal(['name' => 'Open', 'target_amount' => 900]);
        $this->createTransaction($done['id'], ['amount' => 100, 'date' => '2026-05-10']);

        $this->getJson('/api/v1/reports/status?from=2026-05-01&to=2026-05-31')
            ->assertOk()
            ->assertJsonPath('data.completed.count', 1)
            ->assertJsonPath('data.completed.saved_amount', 100)
            ->assertJsonPath('data.active.count', 1)
            ->assertJsonPath('data.active.target_amount', 900)
            ->assertJsonPath('data.completion_rate', 50)
            ->assertJsonPath('data.completed_in_period.0.completed_at', '2026-05-10');

        $this->getJson('/api/v1/reports/status?from=2026-06-01')
            ->assertJsonCount(0, 'data.completed_in_period');
    }
}
