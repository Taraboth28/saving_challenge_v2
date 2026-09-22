<?php

namespace App\Support;

use JsonException;
use RuntimeException;

/**
 * A single JSON file holding one collection of records.
 *
 * File layout: {"next_id": int, "records": [ {...}, ... ]}
 * Writes hold an exclusive file lock for the whole read-modify-write cycle,
 * so concurrent requests cannot overwrite each other's changes.
 */
class JsonStore
{
    public function __construct(private string $path) {}

    /**
     * @return array{next_id: int, records: list<array<string, mixed>>}
     */
    public function read(): array
    {
        if (! is_file($this->path)) {
            return $this->emptyState();
        }

        $handle = $this->open('rb');

        try {
            flock($handle, LOCK_SH);

            return $this->decode((string) stream_get_contents($handle));
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Run a mutation against the stored state under an exclusive lock.
     *
     * The callback receives the state by reference and may return any value,
     * which is passed back to the caller.
     *
     * @template TReturn
     *
     * @param  callable(array{next_id: int, records: list<array<string, mixed>>}&): TReturn  $mutation
     * @return TReturn
     */
    public function write(callable $mutation): mixed
    {
        $this->ensureDirectoryExists();

        $handle = $this->open('c+b');

        try {
            flock($handle, LOCK_EX);

            $state = $this->decode((string) stream_get_contents($handle));
            $result = $mutation($state);
            $state['records'] = array_values($state['records']);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode(
                $state,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            ));
            fflush($handle);

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return resource
     */
    private function open(string $mode)
    {
        $handle = fopen($this->path, $mode);

        if ($handle === false) {
            throw new RuntimeException("Unable to open JSON store [{$this->path}].");
        }

        return $handle;
    }

    /**
     * @return array{next_id: int, records: list<array<string, mixed>>}
     */
    private function decode(string $contents): array
    {
        if (trim($contents) === '') {
            return $this->emptyState();
        }

        try {
            $state = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("JSON store [{$this->path}] is corrupted.", previous: $exception);
        }

        return [
            'next_id' => (int) ($state['next_id'] ?? 1),
            'records' => array_values($state['records'] ?? []),
        ];
    }

    /**
     * @return array{next_id: int, records: list<array<string, mixed>>}
     */
    private function emptyState(): array
    {
        return ['next_id' => 1, 'records' => []];
    }

    private function ensureDirectoryExists(): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create JSON storage directory [{$directory}].");
        }
    }
}
