<?php

namespace App\Repositories\Json;

use App\Repositories\Contracts\RecordRepository;
use App\Support\JsonStore;
use Illuminate\Support\Carbon;

/**
 * Shared CRUD implementation backed by a JsonStore file.
 */
abstract class JsonRepository implements RecordRepository
{
    public function __construct(protected JsonStore $store) {}

    public function all(): array
    {
        return $this->store->read()['records'];
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $record) {
            if ($record['id'] === $id) {
                return $record;
            }
        }

        return null;
    }

    public function create(array $attributes): array
    {
        return $this->store->write(function (array &$state) use ($attributes): array {
            $now = Carbon::now()->toIso8601String();

            $record = ['id' => $state['next_id']] + $attributes + [
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $state['next_id']++;
            $state['records'][] = $record;

            return $record;
        });
    }

    public function update(int $id, array $attributes): ?array
    {
        return $this->store->write(function (array &$state) use ($id, $attributes): ?array {
            foreach ($state['records'] as $index => $record) {
                if ($record['id'] !== $id) {
                    continue;
                }

                unset($attributes['id'], $attributes['created_at']);

                $state['records'][$index] = array_merge($record, $attributes, [
                    'updated_at' => Carbon::now()->toIso8601String(),
                ]);

                return $state['records'][$index];
            }

            return null;
        });
    }

    public function delete(int $id): bool
    {
        return $this->deleteWhere(fn (array $record): bool => $record['id'] === $id) > 0;
    }

    /**
     * @param  callable(array<string, mixed>): bool  $matches
     */
    protected function deleteWhere(callable $matches): int
    {
        return $this->store->write(function (array &$state) use ($matches): int {
            $before = count($state['records']);
            $state['records'] = array_values(array_filter(
                $state['records'],
                fn (array $record): bool => ! $matches($record),
            ));

            return $before - count($state['records']);
        });
    }
}
