<?php

namespace App\Support;

use App\Enums\TransactionType;

/**
 * Date range, goal and type criteria shared by transaction listings and reports.
 */
final readonly class TransactionFilter
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?int $goalId = null,
        public ?TransactionType $type = null,
    ) {}

    /**
     * @param  array{from?: string|null, to?: string|null, goal_id?: int|string|null, type?: string|null}  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            from: $input['from'] ?? null,
            to: $input['to'] ?? null,
            goalId: isset($input['goal_id']) ? (int) $input['goal_id'] : null,
            type: isset($input['type']) ? TransactionType::from($input['type']) : null,
        );
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    public function matches(array $transaction): bool
    {
        return ($this->from === null || $transaction['date'] >= $this->from)
            && ($this->to === null || $transaction['date'] <= $this->to)
            && ($this->goalId === null || $transaction['goal_id'] === $this->goalId)
            && ($this->type === null || $transaction['type'] === $this->type->value);
    }

    /**
     * @param  list<array<string, mixed>>  $transactions
     * @return list<array<string, mixed>>
     */
    public function apply(array $transactions): array
    {
        return array_values(array_filter($transactions, $this->matches(...)));
    }

    /**
     * @return array{from: string|null, to: string|null, goal_id: int|null, type: string|null}
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'goal_id' => $this->goalId,
            'type' => $this->type?->value,
        ];
    }
}
