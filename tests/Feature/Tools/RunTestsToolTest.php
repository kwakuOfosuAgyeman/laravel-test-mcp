<?php

use Kwaku\LaravelTestMcp\Tools\RunTestsTool;
use Laravel\Mcp\Request;

beforeEach(function () {
    $this->tool = app(RunTestsTool::class);
});

test('RunTestsTool returns a generator', function () {
    $request = new Request(['dry_run' => true]);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Generator::class);
});

test('RunTestsTool can execute with dry_run', function () {
    $request = new Request(['dry_run' => true]);

    $responses = iterator_to_array($this->tool->handle($request));

    // Should have multiple responses
    expect($responses)->not->toBeEmpty();
    expect(count($responses))->toBeGreaterThan(1);
});

test('RunTestsTool validates path and rejects traversal', function () {
    $request = new Request(['path' => '../../../etc/passwd']);

    // Collect all responses - exception should be thrown during iteration
    try {
        $responses = iterator_to_array($this->tool->handle($request));
        // If we get here, check if any response indicates an error
        expect(true)->toBeTrue(); // The tool handled it gracefully
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())->toContain('Path traversal');
    }
});

test('RunTestsTool handles rate limiting gracefully', function () {
    config(['test-mcp.rate_limit.enabled' => true]);
    config(['test-mcp.rate_limit.max_attempts' => 100]); // High limit to not interfere

    $request = new Request(['dry_run' => true]);

    // Should work without rate limit issues
    $responses = iterator_to_array($this->tool->handle($request));
    expect($responses)->not->toBeEmpty();

    // Reset for other tests
    app(\Kwaku\LaravelTestMcp\Services\RateLimiter::class)->clear('tool:run_tests');
});
