<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array<string, mixed> $resource
 */
class GoalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $goal = $this->resource;

        return [
            'id' => $goal['id'],
            'name' => $goal['name'],
            'description' => $goal['description'],
            'target_amount' => $goal['target_amount'],
            'target_date' => $goal['target_date'],
            'saved_amount' => $goal['saved_amount'],
            'remaining_amount' => $goal['remaining_amount'],
            'progress' => $goal['progress'],
            'status' => $goal['status'],
            'completed_at' => $goal['completed_at'],
            'transactions_count' => $goal['transactions_count'],
            'last_activity_at' => $goal['last_activity_at'],
            'days_left' => $goal['days_left'],
            'is_overdue' => $goal['is_overdue'],
            'created_at' => $goal['created_at'],
            'updated_at' => $goal['updated_at'],
        ];
    }
}
