<?php

use Kwaku\LaravelTestMcp\Services\ClassAnalyzer;
use Kwaku\LaravelTestMcp\DTOs\ClassAnalysis;

beforeEach(function () {
    $this->analyzer = app(ClassAnalyzer::class);

    // Create a test fixture directory
    $this->fixtureDir = base_path('app/TestFixtures');
    if (!is_dir($this->fixtureDir)) {
        mkdir($this->fixtureDir, 0755, true);
    }
});

afterEach(function () {
    // Clean up test fixtures
    $files = glob($this->fixtureDir . '/*');
    foreach ($files as $file) {
        @unlink($file);
    }
    @rmdir($this->fixtureDir);
});

test('ClassAnalyzer throws exception for non-existent file', function () {
    expect(fn() => $this->analyzer->analyze('app/Models/NonExistent.php'))
        ->toThrow(InvalidArgumentException::class, 'File not found');
});

test('ClassAnalyzer throws exception for non-PHP file', function () {
    // Create a temporary non-PHP file
    $path = base_path('test-file.txt');
    file_put_contents($path, 'not php');

    try {
        expect(fn() => $this->analyzer->analyze('test-file.txt'))
            ->toThrow(InvalidArgumentException::class, 'Only PHP files');
    } finally {
        @unlink($path);
    }
});

test('ClassAnalyzer throws exception for path traversal', function () {
    expect(fn() => $this->analyzer->analyze('../../../etc/passwd'))
        ->toThrow(InvalidArgumentException::class, 'Path traversal');
});

test('ClassAnalyzer throws exception for shell metacharacters', function () {
    expect(fn() => $this->analyzer->analyze('app/Models/User.php; rm -rf /'))
        ->toThrow(InvalidArgumentException::class, 'Invalid characters');
});

test('ClassAnalyzer throws exception for empty path', function () {
    expect(fn() => $this->analyzer->analyze(''))
        ->toThrow(InvalidArgumentException::class, 'Path is required');
});

test('ClassAnalyzer can analyze a simple class', function () {
    // Create a test fixture
    $code = <<<'PHP'
<?php

namespace App\TestFixtures;

class SimpleService
{
    public function doSomething(): string
    {
        return 'done';
    }
}
PHP;
    file_put_contents($this->fixtureDir . '/SimpleService.php', $code);

    $analysis = $this->analyzer->analyze('app/TestFixtures/SimpleService.php');

    expect($analysis)->toBeInstanceOf(ClassAnalysis::class);
    expect($analysis->shortName)->toBe('SimpleService');
    expect($analysis->namespace)->toBe('App\TestFixtures');
});

test('ClassAnalyzer extracts public methods', function () {
    // Create a test fixture with multiple methods
    $code = <<<'PHP'
<?php

namespace App\TestFixtures;

class MethodService
{
    public function index(): void {}
    public function store(string $name): bool { return true; }
    private function privateMethod(): void {}
}
PHP;
    file_put_contents($this->fixtureDir . '/MethodService.php', $code);

    $analysis = $this->analyzer->analyze('app/TestFixtures/MethodService.php');

    $methodNames = array_map(fn($m) => $m->name, $analysis->methods);

    expect($methodNames)->toContain('index');
    expect($methodNames)->toContain('store');
    expect($methodNames)->not->toContain('privateMethod');
});

test('ClassAnalyzer detects service type from path', function () {
    // Create test fixtures in Services directory
    $servicesDir = base_path('app/Services');
    if (!is_dir($servicesDir)) {
        mkdir($servicesDir, 0755, true);
    }

    $code = <<<'PHP'
<?php

namespace App\Services;

class TestService
{
    public function execute(): void {}
}
PHP;
    file_put_contents($servicesDir . '/TestService.php', $code);

    try {
        $analysis = $this->analyzer->analyze('app/Services/TestService.php');
        expect($analysis->type)->toBe('service');
    } finally {
        @unlink($servicesDir . '/TestService.php');
        @rmdir($servicesDir);
    }
});

test('ClassAnalyzer can analyze a class with dependencies', function () {
    $code = <<<'PHP'
<?php

namespace App\TestFixtures;

use App\Repositories\UserRepository;

class DependencyService
{
    public function __construct(
        private UserRepository $repository,
        private string $config = 'default'
    ) {}
}
PHP;
    file_put_contents($this->fixtureDir . '/DependencyService.php', $code);

    $analysis = $this->analyzer->analyze('app/TestFixtures/DependencyService.php');

    expect($analysis->dependencies)->toBeArray();
    // Note: UserRepository won't be in dependencies since it doesn't exist
});

test('ClassAnalyzer includes file path in analysis', function () {
    $code = <<<'PHP'
<?php

namespace App\TestFixtures;

class PathTest
{
}
PHP;
    file_put_contents($this->fixtureDir . '/PathTest.php', $code);

    $analysis = $this->analyzer->analyze('app/TestFixtures/PathTest.php');

    expect($analysis->filePath)->toBe('app/TestFixtures/PathTest.php');
});

test('ClassAnalyzer detects controller type', function () {
    // Create Controllers directory
    $controllersDir = base_path('app/Http/Controllers');
    if (!is_dir($controllersDir)) {
        mkdir($controllersDir, 0755, true);
    }

    $code = <<<'PHP'
<?php

namespace App\Http\Controllers;

class TestController
{
    public function index() {}
    public function store() {}
}
PHP;
    file_put_contents($controllersDir . '/TestController.php', $code);

    try {
        $analysis = $this->analyzer->analyze('app/Http/Controllers/TestController.php');
        expect($analysis->type)->toBe('controller');
    } finally {
        @unlink($controllersDir . '/TestController.php');
        @rmdir($controllersDir);
        @rmdir(base_path('app/Http'));
    }
});
