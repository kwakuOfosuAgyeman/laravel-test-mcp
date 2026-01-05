<?php

use Kwaku\LaravelTestMcp\DTOs\ClassAnalysis;
use Kwaku\LaravelTestMcp\DTOs\MethodInfo;

test('ClassAnalysis can be instantiated with basic properties', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Models\User',
        shortName: 'User',
        namespace: 'App\Models',
        type: 'model',
        parentClass: 'Illuminate\Database\Eloquent\Model',
        methods: [],
        dependencies: [],
        laravelFeatures: [],
        filePath: 'app/Models/User.php',
    );

    expect($analysis->className)->toBe('App\Models\User');
    expect($analysis->shortName)->toBe('User');
    expect($analysis->namespace)->toBe('App\Models');
    expect($analysis->type)->toBe('model');
    expect($analysis->parentClass)->toBe('Illuminate\Database\Eloquent\Model');
    expect($analysis->filePath)->toBe('app/Models/User.php');
});

test('ClassAnalysis can include methods', function () {
    $method = new MethodInfo(
        name: 'posts',
        visibility: 'public',
        isStatic: false,
        returnType: 'HasMany',
        returnTypeNullable: false,
        parameters: [],
    );

    $analysis = new ClassAnalysis(
        className: 'App\Models\User',
        shortName: 'User',
        namespace: 'App\Models',
        type: 'model',
        parentClass: null,
        methods: [$method],
        dependencies: [],
        laravelFeatures: [],
        filePath: 'app/Models/User.php',
    );

    expect($analysis->methods)->toHaveCount(1);
    expect($analysis->methods[0]->name)->toBe('posts');
});

test('ClassAnalysis can include dependencies', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Services\UserService',
        shortName: 'UserService',
        namespace: 'App\Services',
        type: 'service',
        parentClass: null,
        methods: [],
        dependencies: ['App\Repositories\UserRepository', 'App\Services\MailService'],
        laravelFeatures: [],
        filePath: 'app/Services/UserService.php',
    );

    expect($analysis->dependencies)->toHaveCount(2);
    expect($analysis->dependencies[0])->toBe('App\Repositories\UserRepository');
});

test('ClassAnalysis can include Laravel features', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Models\User',
        shortName: 'User',
        namespace: 'App\Models',
        type: 'model',
        parentClass: null,
        methods: [],
        dependencies: [],
        laravelFeatures: [
            'relationships' => [['name' => 'posts', 'type' => 'hasMany']],
            'scopes' => ['active', 'verified'],
            'fillable' => ['name', 'email'],
        ],
        filePath: 'app/Models/User.php',
    );

    expect($analysis->laravelFeatures)->toHaveKey('relationships');
    expect($analysis->laravelFeatures['scopes'])->toContain('active');
});

test('ClassAnalysis isController returns true for controllers', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Http\Controllers\UserController',
        shortName: 'UserController',
        namespace: 'App\Http\Controllers',
        type: 'controller',
        parentClass: null,
        methods: [],
        dependencies: [],
        laravelFeatures: [],
        filePath: 'app/Http/Controllers/UserController.php',
    );

    expect($analysis->isController())->toBeTrue();
    expect($analysis->isModel())->toBeFalse();
});

test('ClassAnalysis isModel returns true for models', function () {
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

    expect($analysis->isModel())->toBeTrue();
    expect($analysis->isController())->toBeFalse();
});

test('ClassAnalysis isFormRequest returns true for form requests', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Http\Requests\StoreUserRequest',
        shortName: 'StoreUserRequest',
        namespace: 'App\Http\Requests',
        type: 'formrequest',
        parentClass: null,
        methods: [],
        dependencies: [],
        laravelFeatures: [],
        filePath: 'app/Http/Requests/StoreUserRequest.php',
    );

    expect($analysis->isFormRequest())->toBeTrue();
});

test('ClassAnalysis isJob returns true for jobs', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Jobs\SendEmail',
        shortName: 'SendEmail',
        namespace: 'App\Jobs',
        type: 'job',
        parentClass: null,
        methods: [],
        dependencies: [],
        laravelFeatures: [],
        filePath: 'app/Jobs/SendEmail.php',
    );

    expect($analysis->isJob())->toBeTrue();
});

test('ClassAnalysis isMiddleware returns true for middleware', function () {
    $analysis = new ClassAnalysis(
        className: 'App\Http\Middleware\Authenticate',
        shortName: 'Authenticate',
        namespace: 'App\Http\Middleware',
        type: 'middleware',
        parentClass: null,
        methods: [],
        dependencies: [],
        laravelFeatures: [],
        filePath: 'app/Http/Middleware/Authenticate.php',
    );

    expect($analysis->isMiddleware())->toBeTrue();
});

test('ClassAnalysis can include traits and interfaces', function () {
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
        traits: ['Illuminate\Database\Eloquent\Factories\HasFactory'],
        interfaces: ['Illuminate\Contracts\Auth\Authenticatable'],
    );

    expect($analysis->traits)->toContain('Illuminate\Database\Eloquent\Factories\HasFactory');
    expect($analysis->interfaces)->toContain('Illuminate\Contracts\Auth\Authenticatable');
});
