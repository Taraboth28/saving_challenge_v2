<?php

namespace Tests\Feature;

use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    public function test_it_adds_a_transaction_with_the_goal_name(): void
    {
        $goal = $this->createGoal();

        $this->postJson('/api/v1/transactions', [
            'goal_id' => $goal['id'],
            'type' => 'deposit',
            'amount' => 25.75,
            'date' => now()->toDateString(),
            'note' => 'Coffee money',
        ])->assertCreated()
            ->assertJsonPath('data.goal_name', 'Emergency Fund')
            ->assertJsonPath('data.amount', 25.75)
            ->assertJsonPath('data.note', 'Coffee money');
    }

    public function test_it_validates_transaction_input(): void
    {
        $this->postJson('/api/v1/transactions', [
            'goal_id' => 999,
            'type' => 'gift',
            'amount' => 0,
            'date' => now()->addDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['goal_id', 'type', 'amount', 'date']);
    }

    public function test_a_withdrawal_cannot_exceed_the_saved_balance(): void
    {
        $goal = $this->createGoal();
        $this->createTransaction($goal['id'], ['amount' => 100]);

        $this->postJson('/api/v1/transactions', [
            'goal_id' => $goal['id'],
            'type' => 'withdrawal',
            'amount' => 150,
            'date' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['amount']);

        $this->createTransaction($goal['id'], ['type' => 'withdrawal', 'amount' => 40]);

        $this->getJson("/api/v1/goals/{$goal['id']}")->assertJsonPath('data.saved_amount', 60);
    }

    public function test_it_updates_and_deletes_a_transaction(): void
    {
        $goal = $this->createGoal();
        $transaction = $this->createTransaction($goal['id'], ['amount' => 100]);

        $this->putJson("/api/v1/transactions/{$transaction['id']}", ['amount' => 300])
            ->assertOk()
            ->assertJsonPath('data.amount', 300);

        $this->getJson("/api/v1/goals/{$goal['id']}")->assertJsonPath('data.saved_amount', 300);

        $this->deleteJson("/api/v1/transactions/{$transaction['id']}")->assertNoContent();

        $this->getJson("/api/v1/goals/{$goal['id']}")->assertJsonPath('data.saved_amount', 0);
        $this->getJson("/api/v1/transactions/{$transaction['id']}")->assertNotFound();
    }

    public function test_editing_a_withdrawal_checks_the_balance_without_itself(): void
    {
        $goal = $this->createGoal();
        $this->createTransaction($goal['id'], ['amount' => 100]);
        $withdrawal = $this->createTransaction($goal['id'], ['type' => 'withdrawal', 'amount' => 80]);

        $this->patchJson("/api/v1/transactions/{$withdrawal['id']}", ['amount' => 100])->assertOk();
        $this->patchJson("/api/v1/transactions/{$withdrawal['id']}", ['amount' => 101])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_it_filters_transactions_by_goal_and_date(): void
    {
        $first = $this->createGoal();
        $second = $this->createGoal(['name' => 'Second']);
        $this->createTransaction($first['id'], ['date' => '2026-01-10']);
        $this->createTransaction($first['id'], ['date' => '2026-03-10']);
        $this->createTransaction($second['id'], ['date' => '2026-03-15']);

        $this->getJson("/api/v1/transactions?goal_id={$first['id']}")->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/transactions?from=2026-03-01&to=2026-03-31')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.date', '2026-03-15');
        $this->getJson('/api/v1/transactions?from=2026-03-01&to=2026-02-01')->assertUnprocessable();
    }
}
