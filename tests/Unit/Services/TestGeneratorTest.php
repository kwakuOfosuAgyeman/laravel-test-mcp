<?php

use Kwaku\LaravelTestMcp\Services\TestGenerator;
use Kwaku\LaravelTestMcp\DTOs\ClassAnalysis;
use Kwaku\LaravelTestMcp\DTOs\MethodInfo;
use Kwaku\LaravelTestMcp\DTOs\GeneratedTest;

beforeEach(function () {
    $this->generator = app(TestGenerator::class);
});

test('TestGenerator generates test for model', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Models\User',
        shortName: 'User',
        namespace: 'App\Models',
        type: 'model',
        parentClass: 'Illuminate\Database\Eloquent\Model',
        methods: [],
        dependencies: [],
        laravelFeatures: [
            'fillable' => ['name', 'email'],
            'relationships' => [],
            'scopes' => [],
            'accessors' => [],
            'mutators' => [],
        ],
        filePath: 'app/Models/User.php',
    );

    $result = $this->generator->generate($analysis);

    expect($result)->toBeInstanceOf(GeneratedTest::class);
    expect($result->testType)->toBe('unit');
    expect($result->testContent)->toContain('test(');
    expect($result->testContent)->toContain('User');
});

test('TestGenerator generates test for controller', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Http\Controllers\UserController',
        shortName: 'UserController',
        namespace: 'App\Http\Controllers',
        type: 'controller',
        parentClass: null,
        methods: [
            new MethodInfo(
                name: 'index',
                visibility: 'public',
                isStatic: false,
                returnType: 'Response',
                returnTypeNullable: false,
            ),
        ],
        dependencies: [],
        laravelFeatures: ['routes' => [], 'resourceActions' => ['index']],
        filePath: 'app/Http/Controllers/UserController.php',
    );

    $result = $this->generator->generate($analysis);

    expect($result)->toBeInstanceOf(GeneratedTest::class);
    expect($result->testType)->toBe('feature');
    expect($result->testContent)->toContain('index');
});

test('TestGenerator generates test for service', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Services\UserService',
        shortName: 'UserService',
        namespace: 'App\Services',
        type: 'service',
        parentClass: null,
        methods: [
            new MethodInfo(
                name: 'findUser',
                visibility: 'public',
                isStatic: false,
                returnType: 'User',
                returnTypeNullable: true,
                parameters: [
                    ['name' => 'id', 'type' => 'int', 'nullable' => false, 'hasDefault' => false, 'default' => null],
                ],
            ),
        ],
        dependencies: ['App\Repositories\UserRepository'],
        laravelFeatures: [],
        filePath: 'app/Services/UserService.php',
    );

    $result = $this->generator->generate($analysis);

    expect($result)->toBeInstanceOf(GeneratedTest::class);
    expect($result->testType)->toBe('unit');
    expect($result->testContent)->toContain('findUser');
    expect($result->testContent)->toContain('Mockery');
});

test('TestGenerator generates test for job', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Jobs\SendEmail',
        shortName: 'SendEmail',
        namespace: 'App\Jobs',
        type: 'job',
        parentClass: null,
        methods: [
            new MethodInfo(
                name: 'handle',
                visibility: 'public',
                isStatic: false,
                returnType: 'void',
                returnTypeNullable: false,
            ),
        ],
        dependencies: [],
        laravelFeatures: ['hasHandle' => true, 'isQueueable' => true],
        filePath: 'app/Jobs/SendEmail.php',
    );

    $result = $this->generator->generate($analysis);

    expect($result->testContent)->toContain('Bus::fake()');
    expect($result->testContent)->toContain('dispatch');
});

test('TestGenerator generates test for middleware', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Http\Middleware\CheckAge',
        shortName: 'CheckAge',
        namespace: 'App\Http\Middleware',
        type: 'middleware',
        parentClass: null,
        methods: [
            new MethodInfo(
                name: 'handle',
                visibility: 'public',
                isStatic: false,
                returnType: 'Response',
                returnTypeNullable: false,
            ),
        ],
        dependencies: [],
        laravelFeatures: ['hasHandle' => true, 'hasTerminate' => false],
        filePath: 'app/Http/Middleware/CheckAge.php',
    );

    $result = $this->generator->generate($analysis);

    expect($result->testType)->toBe('feature');
    expect($result->testContent)->toContain('Request::create');
    expect($result->testContent)->toContain('middleware->handle');
});

test('TestGenerator suggests correct test path', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Models\User',
        shortName: 'User',
        namespace: 'App\Models',
        type: 'model',
        parentClass: null,
        methods: [],
        dependencies: [],
        laravelFeatures: [],
        filePath: 'app/Models/User.php',
    );

    $result = $this->generator->generate($analysis);

    expect($result->suggestedTestPath)->toContain('tests/');
    expect($result->suggestedTestPath)->toContain('UserTest.php');
});

test('TestGenerator includes coverage information', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Services\UserService',
        shortName: 'UserService',
        namespace: 'App\Services',
        type: 'service',
        parentClass: null,
        methods: [
            new MethodInfo(
                name: 'create',
                visibility: 'public',
                isStatic: false,
                returnType: 'User',
                returnTypeNullable: false,
            ),
            new MethodInfo(
                name: 'update',
                visibility: 'public',
                isStatic: false,
                returnType: 'User',
                returnTypeNullable: false,
            ),
        ],
        dependencies: [],
        laravelFeatures: [],
        filePath: 'app/Services/UserService.php',
    );

    $result = $this->generator->generate($analysis);

    expect($result->coverage)->toContain('create()');
    expect($result->coverage)->toContain('update()');
});

test('TestGenerator includes todos', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Services\UserService',
        shortName: 'UserService',
        namespace: 'App\Services',
        type: 'service',
        parentClass: null,
        methods: [],
        dependencies: ['App\Repositories\UserRepository'],
        laravelFeatures: [],
        filePath: 'app/Services/UserService.php',
    );

    $result = $this->generator->generate($analysis);

    expect($result->todos)->not->toBeEmpty();
});

test('TestGenerator generates factory for models', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Models\User',
        shortName: 'User',
        namespace: 'App\Models',
        type: 'model',
        parentClass: null,
        methods: [],
        dependencies: [],
        laravelFeatures: [
            'fillable' => ['name', 'email', 'password'],
        ],
        filePath: 'app/Models/User.php',
    );

    $result = $this->generator->generate($analysis);

    expect($result->hasFactory())->toBeTrue();
    expect($result->factoryContent)->toContain('UserFactory');
    expect($result->factoryContent)->toContain("'name'");
    expect($result->factoryContent)->toContain("'email'");
});

test('TestGenerator does not generate factory for non-models', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Services\UserService',
        shortName: 'UserService',
        namespace: 'App\Services',
        type: 'service',
        parentClass: null,
        methods: [],
        dependencies: [],
        laravelFeatures: [],
        filePath: 'app/Services/UserService.php',
    );

    $result = $this->generator->generate($analysis);

    expect($result->hasFactory())->toBeFalse();
});

test('TestGenerator can exclude comments', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Services\UserService',
        shortName: 'UserService',
        namespace: 'App\Services',
        type: 'service',
        parentClass: null,
        methods: [
            new MethodInfo(
                name: 'create',
                visibility: 'public',
                isStatic: false,
                returnType: 'User',
                returnTypeNullable: false,
            ),
        ],
        dependencies: [],
        laravelFeatures: [],
        filePath: 'app/Services/UserService.php',
    );

    $resultWithComments = $this->generator->generate($analysis, true);
    $resultWithoutComments = $this->generator->generate($analysis, false);

    // The one with comments should be longer
    expect(strlen($resultWithComments->testContent))->toBeGreaterThanOrEqual(strlen($resultWithoutComments->testContent));
});
