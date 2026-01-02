<?php

namespace Kwaku\LaravelTestMcp\Services;

use Illuminate\Support\Facades\Cache;

class RateLimiter
{
    private const CACHE_PREFIX = 'test-mcp-rate-limit:';

    /**
     * Check if the action is rate limited.
     *
     * @param string $key Unique identifier for the action (e.g., 'run_tests', 'get_coverage')
     * @param int $maxAttempts Maximum attempts allowed in the decay period
     * @param int $decaySeconds Time window in seconds
     * @return bool True if rate limited (should block), false if allowed
     */
    public function isLimited(string $key, ?int $maxAttempts = null, ?int $decaySeconds = null): bool
    {
        $maxAttempts = $maxAttempts ?? config('test-mcp.rate_limit.max_attempts', 60);
        $decaySeconds = $decaySeconds ?? config('test-mcp.rate_limit.decay_seconds', 60);

        if (!config('test-mcp.rate_limit.enabled', true)) {
            return false;
        }

        $cacheKey = self::CACHE_PREFIX . $key;
        $attempts = (int) Cache::get($cacheKey, 0);

        return $attempts >= $maxAttempts;
    }

    /**
     * Record an attempt for the given key.
     *
     * @param string $key Unique identifier for the action
     * @param int $decaySeconds Time window in seconds
     */
    public function hit(string $key, ?int $decaySeconds = null): int
    {
        $decaySeconds = $decaySeconds ?? config('test-mcp.rate_limit.decay_seconds', 60);
        $cacheKey = self::CACHE_PREFIX . $key;

        $attempts = (int) Cache::get($cacheKey, 0);
        $attempts++;

        Cache::put($cacheKey, $attempts, $decaySeconds);

        return $attempts;
    }

    /**
     * Get remaining attempts for the given key.
     *
     * @param string $key Unique identifier for the action
     * @param int $maxAttempts Maximum attempts allowed
     * @return int Remaining attempts
     */
    public function remaining(string $key, ?int $maxAttempts = null): int
    {
        $maxAttempts = $maxAttempts ?? config('test-mcp.rate_limit.max_attempts', 60);
        $cacheKey = self::CACHE_PREFIX . $key;

        $attempts = (int) Cache::get($cacheKey, 0);

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Clear rate limit for a key.
     *
     * @param string $key Unique identifier for the action
     */
    public function clear(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Attempt an action with rate limiting.
     * Returns true if action is allowed, false if rate limited.
     *
     * @param string $key Unique identifier for the action
     * @param int|null $maxAttempts Maximum attempts allowed
     * @param int|null $decaySeconds Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public function attempt(string $key, ?int $maxAttempts = null, ?int $decaySeconds = null): bool
    {
        if ($this->isLimited($key, $maxAttempts, $decaySeconds)) {
            return false;
        }

        $this->hit($key, $decaySeconds);
        return true;
    }
}
