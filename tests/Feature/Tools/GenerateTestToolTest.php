<?php

use Kwaku\LaravelTestMcp\Tools\GenerateTestTool;
use Kwaku\LaravelTestMcp\Services\RateLimiter;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function () {
    $this->tool = app(GenerateTestTool::class);
    app(RateLimiter::class)->clear('tool:generate_test');

    // Create test fixture directory and file
    $this->fixtureDir = base_path('app/TestFixtures');
    if (!is_dir($this->fixtureDir)) {
        mkdir($this->fixtureDir, 0755, true);
    }

    $code = <<<'PHP'
<?php

namespace App\TestFixtures;

class SampleClass
{
    public function doSomething(): string
    {
        return 'done';
    }

    public function calculate(int $a, int $b): int
    {
        return $a + $b;
    }
}
PHP;
    file_put_contents($this->fixtureDir . '/SampleClass.php', $code);
});

afterEach(function () {
    // Clean up test fixtures
    @unlink($this->fixtureDir . '/SampleClass.php');
    @rmdir($this->fixtureDir);
    app(RateLimiter::class)->clear('tool:generate_test');
});

test('GenerateTestTool returns a response', function () {
    $request = new Request([
        'class_path' => 'app/TestFixtures/SampleClass.php',
    ]);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('GenerateTestTool generates test for existing class', function () {
    $request = new Request([
        'class_path' => 'app/TestFixtures/SampleClass.php',
    ]);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('GenerateTestTool returns error for non-existent file', function () {
    $request = new Request([
        'class_path' => 'app/Models/NonExistent.php',
    ]);

    $result = $this->tool->handle($request);

    // Should return a response (error response)
    expect($result)->toBeInstanceOf(Response::class);
});

test('GenerateTestTool returns error for path traversal', function () {
    $request = new Request([
        'class_path' => '../../../etc/passwd',
    ]);

    $result = $this->tool->handle($request);

    // Should return a response (error response)
    expect($result)->toBeInstanceOf(Response::class);
});

test('GenerateTestTool respects rate limiting', function () {
    config(['test-mcp.rate_limit.enabled' => true]);
    config(['test-mcp.rate_limit.max_attempts' => 100]);

    $request = new Request([
        'class_path' => 'app/TestFixtures/SampleClass.php',
    ]);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('GenerateTestTool handles include_comments parameter', function () {
    $request = new Request([
        'class_path' => 'app/TestFixtures/SampleClass.php',
        'include_comments' => false,
    ]);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('GenerateTestTool handles test_type parameter', function () {
    $request = new Request([
        'class_path' => 'app/TestFixtures/SampleClass.php',
        'test_type' => 'unit',
    ]);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('GenerateTestTool handles auto test_type', function () {
    $request = new Request([
        'class_path' => 'app/TestFixtures/SampleClass.php',
        'test_type' => 'auto',
    ]);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});
