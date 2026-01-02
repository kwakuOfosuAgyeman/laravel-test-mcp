<?php

namespace Kwaku\LaravelTestMcp\Concerns;

use Kwaku\LaravelTestMcp\Services\RateLimiter;
use Laravel\Mcp\Response;

trait HasRateLimiting
{
    /**
     * Check rate limit and return error response if limited.
     *
     * @param string|null $key Override the default key (tool name)
     * @return Response|null Returns error response if rate limited, null if allowed
     */
    protected function checkRateLimit(?string $key = null): ?Response
    {
        $rateLimiter = app(RateLimiter::class);
        $key = $key ?? $this->getRateLimitKey();

        if (!$rateLimiter->attempt($key)) {
            $remaining = $rateLimiter->remaining($key);
            $decaySeconds = config('test-mcp.rate_limit.decay_seconds', 60);

            return Response::error(
                "Rate limit exceeded. Too many requests. Please wait {$decaySeconds} seconds before trying again."
            );
        }

        return null;
    }

    /**
     * Get the rate limit key for this tool.
     */
    protected function getRateLimitKey(): string
    {
        return 'tool:' . ($this->name ?? static::class);
    }
}
