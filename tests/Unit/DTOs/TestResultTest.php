<?php

use Kwaku\LaravelTestMcp\DTOs\TestResult;
use Kwaku\LaravelTestMcp\DTOs\TestFailure;
use Kwaku\LaravelTestMcp\DTOs\PassedTest;

test('TestResult can be instantiated with basic properties', function () {
    $result = new TestResult(
        passed: true,
        totalCount: 10,
        passedCount: 10,
        failedCount: 0,
        duration: 1.5,
    );

    expect($result->passed)->toBeTrue();
    expect($result->totalCount)->toBe(10);
    expect($result->passedCount)->toBe(10);
    expect($result->failedCount)->toBe(0);
    expect($result->duration)->toBe(1.5);
    expect($result->failures)->toBeEmpty();
    expect($result->passedTests)->toBeEmpty();
});

test('TestResult can include failures', function () {
    $failure = new TestFailure(
        test: 'UserTest::test_user_can_login',
        file: '/app/tests/Unit/UserTest.php',
        line: 25,
        message: 'Failed asserting that false is true.',
    );

    $result = new TestResult(
        passed: false,
        totalCount: 10,
        passedCount: 9,
        failedCount: 1,
        duration: 2.0,
        failures: [$failure],
    );

    expect($result->passed)->toBeFalse();
    expect($result->failures)->toHaveCount(1);
    expect($result->failures[0]->test)->toBe('UserTest::test_user_can_login');
});

test('TestResult can include passed tests', function () {
    $passedTest = new PassedTest(
        name: 'UserTest::test_user_can_register',
        duration: 0.05,
    );

    $result = new TestResult(
        passed: true,
        totalCount: 1,
        passedCount: 1,
        failedCount: 0,
        duration: 0.05,
        passedTests: [$passedTest],
    );

    expect($result->passedTests)->toHaveCount(1);
    expect($result->passedTests[0]->name)->toBe('UserTest::test_user_can_register');
    expect($result->passedTests[0]->duration)->toBe(0.05);
});

test('TestFailure can include diff', function () {
    $failure = new TestFailure(
        test: 'UserTest::test_email_format',
        file: '/app/tests/Unit/UserTest.php',
        line: 30,
        message: 'Failed asserting that two strings are equal.',
        diff: "--- Expected\n+++ Actual\n@@ @@\n-'expected@email.com'\n+'actual@email.com'",
    );

    expect($failure->diff)->toContain('Expected');
    expect($failure->diff)->toContain('Actual');
});

test('TestFailure diff is nullable', function () {
    $failure = new TestFailure(
        test: 'UserTest::test_user_exists',
        file: '/app/tests/Unit/UserTest.php',
        line: 15,
        message: 'Failed asserting that null is not null.',
    );

    expect($failure->diff)->toBeNull();
});
