<?php

namespace App\Repositories\Contracts;

/**
 * Basic CRUD over a collection of array records keyed by an integer id.
 */
interface RecordRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array;

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function create(array $attributes): array;

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>|null
     */
    public function update(int $id, array $attributes): ?array;

    public function delete(int $id): bool;
}
