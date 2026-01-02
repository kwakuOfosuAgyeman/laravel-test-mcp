<?php

use Kwaku\LaravelTestMcp\Resources\TestResultsResource;
use Laravel\Mcp\Response;

beforeEach(function () {
    $this->resource = app(TestResultsResource::class);
    $this->storagePath = storage_path('test-mcp');

    // Ensure storage directory exists
    if (!is_dir($this->storagePath)) {
        mkdir($this->storagePath, 0755, true);
    }
});

afterEach(function () {
    // Clean up test files
    $resultsPath = storage_path('test-mcp/latest-results.json');
    if (file_exists($resultsPath)) {
        unlink($resultsPath);
    }
});

test('TestResultsResource returns a response', function () {
    $result = $this->resource->read();

    expect($result)->toBeInstanceOf(Response::class);
});

test('TestResultsResource works when no results exist', function () {
    // Ensure no results file exists
    $resultsPath = storage_path('test-mcp/latest-results.json');
    if (file_exists($resultsPath)) {
        unlink($resultsPath);
    }

    $result = $this->resource->read();

    expect($result)->toBeInstanceOf(Response::class);
});

test('TestResultsResource reads existing results', function () {
    $resultsPath = storage_path('test-mcp/latest-results.json');
    file_put_contents($resultsPath, json_encode([
        'passed' => true,
        'totalCount' => 10,
        'passedCount' => 10,
        'failedCount' => 0,
        'duration' => 1.5,
        'timestamp' => date('c'),
        'failures' => [],
    ]));

    $result = $this->resource->read();

    expect($result)->toBeInstanceOf(Response::class);
});

test('TestResultsResource reads failed results', function () {
    $resultsPath = storage_path('test-mcp/latest-results.json');
    file_put_contents($resultsPath, json_encode([
        'passed' => false,
        'totalCount' => 10,
        'passedCount' => 8,
        'failedCount' => 2,
        'duration' => 2.5,
        'timestamp' => date('c'),
        'failures' => [
            [
                'test' => 'UserTest::test_can_login',
                'file' => 'tests/Unit/UserTest.php',
                'line' => 25,
                'message' => 'Failed asserting that false is true',
            ],
        ],
    ]));

    $result = $this->resource->read();

    expect($result)->toBeInstanceOf(Response::class);
});
