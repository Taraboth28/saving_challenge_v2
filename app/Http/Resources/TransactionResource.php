<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array<string, mixed> $resource
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $transaction = $this->resource;

        return [
            'id' => $transaction['id'],
            'goal_id' => $transaction['goal_id'],
            'goal_name' => $transaction['goal_name'] ?? null,
            'type' => $transaction['type'],
            'amount' => $transaction['amount'],
            'date' => $transaction['date'],
            'note' => $transaction['note'],
            'created_at' => $transaction['created_at'],
            'updated_at' => $transaction['updated_at'],
        ];
    }
}
