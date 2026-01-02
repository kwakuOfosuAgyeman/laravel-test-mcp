<?php

namespace Kwaku\LaravelTestMcp\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CancellationToken
{
    private const CACHE_PREFIX = 'test-mcp-cancel:';
    private const DEFAULT_TTL = 600; // 10 minutes

    private string $operationId;

    public function __construct(?string $operationId = null)
    {
        $this->operationId = $operationId ?? $this->generateId();
    }

    public static function create(): self
    {
        return new self();
    }

    public static function forOperation(string $operationId): self
    {
        return new self($operationId);
    }

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function cancel(): void
    {
        Cache::put(
            $this->getCacheKey(),
            true,
            self::DEFAULT_TTL
        );
    }

    public function isCancelled(): bool
    {
        return (bool) Cache::get($this->getCacheKey(), false);
    }

    public function reset(): void
    {
        Cache::forget($this->getCacheKey());
    }

    private function getCacheKey(): string
    {
        return self::CACHE_PREFIX . $this->operationId;
    }

    private function generateId(): string
    {
        return 'op_' . Str::random(12);
    }
}
