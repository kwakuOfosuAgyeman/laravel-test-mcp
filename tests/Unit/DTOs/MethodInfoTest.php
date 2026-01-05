<?php

use Kwaku\LaravelTestMcp\DTOs\MethodInfo;

test('MethodInfo can be instantiated with basic properties', function () {
    $method = new MethodInfo(
        name: 'index',
        visibility: 'public',
        isStatic: false,
        returnType: 'Response',
        returnTypeNullable: false,
    );

    expect($method->name)->toBe('index');
    expect($method->visibility)->toBe('public');
    expect($method->isStatic)->toBeFalse();
    expect($method->returnType)->toBe('Response');
    expect($method->returnTypeNullable)->toBeFalse();
    expect($method->parameters)->toBeEmpty();
});

test('MethodInfo can include parameters', function () {
    $method = new MethodInfo(
        name: 'store',
        visibility: 'public',
        isStatic: false,
        returnType: 'Response',
        returnTypeNullable: false,
        parameters: [
            ['name' => 'request', 'type' => 'Request', 'nullable' => false, 'hasDefault' => false, 'default' => null],
            ['name' => 'id', 'type' => 'int', 'nullable' => false, 'hasDefault' => true, 'default' => 1],
        ],
    );

    expect($method->parameters)->toHaveCount(2);
    expect($method->parameters[0]['name'])->toBe('request');
    expect($method->parameters[1]['hasDefault'])->toBeTrue();
    expect($method->parameters[1]['default'])->toBe(1);
});

test('MethodInfo can have nullable return type', function () {
    $method = new MethodInfo(
        name: 'find',
        visibility: 'public',
        isStatic: false,
        returnType: 'User',
        returnTypeNullable: true,
    );

    expect($method->returnTypeNullable)->toBeTrue();
});

test('MethodInfo can be static', function () {
    $method = new MethodInfo(
        name: 'boot',
        visibility: 'public',
        isStatic: true,
        returnType: 'void',
        returnTypeNullable: false,
    );

    expect($method->isStatic)->toBeTrue();
});

test('MethodInfo can have null return type', function () {
    $method = new MethodInfo(
        name: 'doSomething',
        visibility: 'public',
        isStatic: false,
        returnType: null,
        returnTypeNullable: false,
    );

    expect($method->returnType)->toBeNull();
});

test('MethodInfo can include docblock', function () {
    $method = new MethodInfo(
        name: 'index',
        visibility: 'public',
        isStatic: false,
        returnType: 'Response',
        returnTypeNullable: false,
        docblock: '/** Get all users */',
    );

    expect($method->docblock)->toBe('/** Get all users */');
});

test('MethodInfo can include line number', function () {
    $method = new MethodInfo(
        name: 'index',
        visibility: 'public',
        isStatic: false,
        returnType: 'Response',
        returnTypeNullable: false,
        line: 42,
    );

    expect($method->line)->toBe(42);
});

test('MethodInfo defaults line to zero', function () {
    $method = new MethodInfo(
        name: 'index',
        visibility: 'public',
        isStatic: false,
        returnType: null,
        returnTypeNullable: false,
    );

    expect($method->line)->toBe(0);
});
