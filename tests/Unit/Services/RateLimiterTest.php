<?php

use Illuminate\Support\Facades\Cache;
use Kwaku\LaravelTestMcp\Services\RateLimiter;

beforeEach(function () {
    Cache::flush();
    $this->rateLimiter = app(RateLimiter::class);
});

test('RateLimiter allows requests under the limit', function () {
    config(['test-mcp.rate_limit.enabled' => true]);
    config(['test-mcp.rate_limit.max_attempts' => 5]);

    expect($this->rateLimiter->attempt('test-key'))->toBeTrue();
    expect($this->rateLimiter->attempt('test-key'))->toBeTrue();
    expect($this->rateLimiter->attempt('test-key'))->toBeTrue();
});

test('RateLimiter blocks requests at the limit', function () {
    config(['test-mcp.rate_limit.enabled' => true]);
    config(['test-mcp.rate_limit.max_attempts' => 3]);

    expect($this->rateLimiter->attempt('test-key'))->toBeTrue();
    expect($this->rateLimiter->attempt('test-key'))->toBeTrue();
    expect($this->rateLimiter->attempt('test-key'))->toBeTrue();
    expect($this->rateLimiter->attempt('test-key'))->toBeFalse();
});

test('RateLimiter tracks remaining attempts', function () {
    config(['test-mcp.rate_limit.enabled' => true]);
    config(['test-mcp.rate_limit.max_attempts' => 5]);

    expect($this->rateLimiter->remaining('test-key'))->toBe(5);

    $this->rateLimiter->hit('test-key');
    expect($this->rateLimiter->remaining('test-key'))->toBe(4);

    $this->rateLimiter->hit('test-key');
    expect($this->rateLimiter->remaining('test-key'))->toBe(3);
});

test('RateLimiter can be cleared', function () {
    config(['test-mcp.rate_limit.enabled' => true]);
    config(['test-mcp.rate_limit.max_attempts' => 2]);

    $this->rateLimiter->hit('test-key');
    $this->rateLimiter->hit('test-key');

    expect($this->rateLimiter->isLimited('test-key'))->toBeTrue();

    $this->rateLimiter->clear('test-key');

    expect($this->rateLimiter->isLimited('test-key'))->toBeFalse();
    expect($this->rateLimiter->remaining('test-key'))->toBe(2);
});

test('RateLimiter respects disabled configuration', function () {
    config(['test-mcp.rate_limit.enabled' => false]);
    config(['test-mcp.rate_limit.max_attempts' => 1]);

    // Should always allow when disabled
    expect($this->rateLimiter->attempt('test-key'))->toBeTrue();
    expect($this->rateLimiter->attempt('test-key'))->toBeTrue();
    expect($this->rateLimiter->attempt('test-key'))->toBeTrue();
    expect($this->rateLimiter->isLimited('test-key'))->toBeFalse();
});

test('RateLimiter uses separate keys for different operations', function () {
    config(['test-mcp.rate_limit.enabled' => true]);
    config(['test-mcp.rate_limit.max_attempts' => 2]);

    $this->rateLimiter->hit('key-a');
    $this->rateLimiter->hit('key-a');

    expect($this->rateLimiter->isLimited('key-a'))->toBeTrue();
    expect($this->rateLimiter->isLimited('key-b'))->toBeFalse();
});
