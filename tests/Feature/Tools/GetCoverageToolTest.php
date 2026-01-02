<?php

use Kwaku\LaravelTestMcp\Tools\GetCoverageTool;
use Laravel\Mcp\Request;

beforeEach(function () {
    $this->tool = app(GetCoverageTool::class);
});

test('GetCoverageTool returns a generator', function () {
    $request = new Request(['dry_run' => true]);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Generator::class);
});

test('GetCoverageTool can execute with dry_run', function () {
    $request = new Request(['dry_run' => true]);

    $responses = iterator_to_array($this->tool->handle($request));

    expect($responses)->not->toBeEmpty();
    expect(count($responses))->toBeGreaterThan(1);
});

test('GetCoverageTool accepts filter_file parameter', function () {
    $request = new Request([
        'dry_run' => true,
        'filter_file' => 'app/Models/User.php',
    ]);

    $responses = iterator_to_array($this->tool->handle($request));

    expect($responses)->not->toBeEmpty();
});

test('GetCoverageTool accepts min_coverage parameter', function () {
    $request = new Request([
        'dry_run' => true,
        'min_coverage' => 80,
    ]);

    $responses = iterator_to_array($this->tool->handle($request));

    expect($responses)->not->toBeEmpty();
});

test('GetCoverageTool handles rate limiting gracefully', function () {
    config(['test-mcp.rate_limit.enabled' => true]);
    config(['test-mcp.rate_limit.max_attempts' => 100]);

    $request = new Request(['dry_run' => true]);
    $responses = iterator_to_array($this->tool->handle($request));

    expect($responses)->not->toBeEmpty();

    app(\Kwaku\LaravelTestMcp\Services\RateLimiter::class)->clear('tool:get_coverage');
});
