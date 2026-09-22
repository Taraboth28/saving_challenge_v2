<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    private string $jsonStoragePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jsonStoragePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'saving-challenge-tests-'.uniqid();
        config(['savings.storage_path' => $this->jsonStoragePath]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->jsonStoragePath);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function createGoal(array $attributes = []): array
    {
        return $this->postJson('/api/v1/goals', $attributes + [
            'name' => 'Emergency Fund',
            'target_amount' => 1000,
        ])->assertCreated()->json('data');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function createTransaction(int $goalId, array $attributes = []): array
    {
        return $this->postJson('/api/v1/transactions', $attributes + [
            'goal_id' => $goalId,
            'type' => 'deposit',
            'amount' => 100,
            'date' => now()->toDateString(),
        ])->assertCreated()->json('data');
    }
}
