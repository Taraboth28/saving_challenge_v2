<?php

namespace Tests\Feature;

use Tests\TestCase;

class GoalApiTest extends TestCase
{
    public function test_it_creates_a_goal_and_persists_it_to_json(): void
    {
        $response = $this->postJson('/api/v1/goals', [
            'name' => 'New Laptop',
            'description' => 'For work',
            'target_amount' => 1200.5,
            'target_date' => now()->addMonths(3)->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Laptop')
            ->assertJsonPath('data.target_amount', 1200.5)
            ->assertJsonPath('data.saved_amount', 0)
            ->assertJsonPath('data.remaining_amount', 1200.5)
            ->assertJsonPath('data.progress', 0)
            ->assertJsonPath('data.status', 'active');

        $stored = json_decode(file_get_contents(config('savings.storage_path').'/goals.json'), true);

        $this->assertSame('New Laptop', $stored['records'][0]['name']);
        $this->assertSame(2, $stored['next_id']);
    }

    public function test_it_validates_goal_input(): void
    {
        $this->postJson('/api/v1/goals', [
            'target_amount' => -5,
            'target_date' => now()->subDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'target_amount', 'target_date']);
    }

    public function test_it_lists_goals_and_filters_by_status(): void
    {
        $completed = $this->createGoal(['name' => 'Small goal', 'target_amount' => 50]);
        $this->createGoal(['name' => 'Big goal', 'target_amount' => 5000]);
        $this->createTransaction($completed['id'], ['amount' => 50]);

        $this->getJson('/api/v1/goals')->assertOk()->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/goals?status=completed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Small goal');
    }

    public function test_it_shows_goal_details_with_progress_and_history(): void
    {
        $goal = $this->createGoal(['target_amount' => 400]);
        $this->createTransaction($goal['id'], ['amount' => 150, 'date' => now()->subDays(2)->toDateString()]);
        $this->createTransaction($goal['id'], ['amount' => 50]);

        $this->getJson("/api/v1/goals/{$goal['id']}")
            ->assertOk()
            ->assertJsonPath('data.saved_amount', 200)
            ->assertJsonPath('data.remaining_amount', 200)
            ->assertJsonPath('data.progress', 50)
            ->assertJsonPath('data.transactions_count', 2)
            ->assertJsonCount(2, 'transactions')
            ->assertJsonPath('transactions.0.amount', 50);
    }

    public function test_a_goal_is_completed_once_the_target_is_reached(): void
    {
        $goal = $this->createGoal(['target_amount' => 100]);
        $this->createTransaction($goal['id'], ['amount' => 120]);

        $this->getJson("/api/v1/goals/{$goal['id']}")
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress', 100)
            ->assertJsonPath('data.remaining_amount', 0)
            ->assertJsonPath('data.completed_at', now()->toDateString());
    }

    public function test_it_updates_only_the_given_fields(): void
    {
        $goal = $this->createGoal(['description' => 'Keep me']);

        $this->patchJson("/api/v1/goals/{$goal['id']}", ['target_amount' => 2000])
            ->assertOk()
            ->assertJsonPath('data.target_amount', 2000)
            ->assertJsonPath('data.description', 'Keep me');
    }

    public function test_deleting_a_goal_removes_its_transactions(): void
    {
        $goal = $this->createGoal();
        $other = $this->createGoal(['name' => 'Other']);
        $this->createTransaction($goal['id']);
        $this->createTransaction($other['id']);

        $this->deleteJson("/api/v1/goals/{$goal['id']}")->assertNoContent();

        $this->getJson("/api/v1/goals/{$goal['id']}")->assertNotFound();
        $this->getJson('/api/v1/transactions')->assertJsonCount(1, 'data');
    }

    public function test_missing_goals_return_not_found(): void
    {
        $this->getJson('/api/v1/goals/999')->assertNotFound();
        $this->patchJson('/api/v1/goals/999', ['name' => 'x'])->assertNotFound();
        $this->deleteJson('/api/v1/goals/999')->assertNotFound();
    }
}
