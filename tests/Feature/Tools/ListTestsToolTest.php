<?php

use Kwaku\LaravelTestMcp\Tools\ListTestsTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function () {
    $this->tool = app(ListTestsTool::class);
});

test('ListTestsTool returns a response', function () {
    $request = new Request(['path' => 'tests']);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('ListTestsTool works with tree format', function () {
    $request = new Request(['path' => 'tests', 'format' => 'tree']);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('ListTestsTool works with flat format', function () {
    $request = new Request(['path' => 'tests', 'format' => 'flat']);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('ListTestsTool works with json format', function () {
    $request = new Request(['path' => 'tests', 'format' => 'json']);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('ListTestsTool handles rate limiting gracefully', function () {
    config(['test-mcp.rate_limit.enabled' => true]);
    config(['test-mcp.rate_limit.max_attempts' => 100]);

    $request = new Request(['path' => 'tests']);
    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);

    // Reset for other tests
    app(\Kwaku\LaravelTestMcp\Services\RateLimiter::class)->clear('tool:list_tests');
});
