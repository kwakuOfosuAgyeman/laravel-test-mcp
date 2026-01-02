<?php

use Kwaku\LaravelTestMcp\DTOs\ProgressUpdate;

test('ProgressUpdate can be instantiated with basic properties', function () {
    $progress = new ProgressUpdate(
        stage: 'Running tests',
        completed: 5,
        total: 10,
        percentComplete: 50.0,
    );

    expect($progress->stage)->toBe('Running tests');
    expect($progress->completed)->toBe(5);
    expect($progress->total)->toBe(10);
    expect($progress->percentComplete)->toBe(50.0);
    expect($progress->currentItem)->toBeNull();
    expect($progress->operationId)->toBeNull();
});

test('ProgressUpdate can include current item', function () {
    $progress = new ProgressUpdate(
        stage: 'Running tests',
        completed: 3,
        total: 10,
        percentComplete: 30.0,
        currentItem: 'UserTest::test_can_login',
    );

    expect($progress->currentItem)->toBe('UserTest::test_can_login');
});

test('ProgressUpdate can include operation ID', function () {
    $progress = new ProgressUpdate(
        stage: 'Running tests',
        completed: 3,
        total: 10,
        percentComplete: 30.0,
        operationId: 'op_abc123',
    );

    expect($progress->operationId)->toBe('op_abc123');
});

test('ProgressUpdate format returns formatted string without current item', function () {
    $progress = new ProgressUpdate(
        stage: 'Running tests',
        completed: 5,
        total: 10,
        percentComplete: 50.0,
    );

    $formatted = $progress->format();

    expect($formatted)->toContain('Running tests');
    expect($formatted)->toContain('[5/10]');
    expect($formatted)->toContain('50.0%');
});

test('ProgressUpdate format includes current item when provided', function () {
    $progress = new ProgressUpdate(
        stage: 'Running tests',
        completed: 5,
        total: 10,
        percentComplete: 50.0,
        currentItem: 'UserTest',
    );

    $formatted = $progress->format();

    expect($formatted)->toContain('UserTest');
});

test('ProgressUpdate format handles zero percent', function () {
    $progress = new ProgressUpdate(
        stage: 'Starting',
        completed: 0,
        total: 100,
        percentComplete: 0.0,
    );

    $formatted = $progress->format();

    expect($formatted)->toContain('[0/100]');
    expect($formatted)->toContain('0.0%');
});

test('ProgressUpdate format handles 100 percent', function () {
    $progress = new ProgressUpdate(
        stage: 'Complete',
        completed: 100,
        total: 100,
        percentComplete: 100.0,
    );

    $formatted = $progress->format();

    expect($formatted)->toContain('[100/100]');
    expect($formatted)->toContain('100.0%');
});

test('ProgressUpdate format handles decimal percentages', function () {
    $progress = new ProgressUpdate(
        stage: 'Running',
        completed: 1,
        total: 3,
        percentComplete: 33.333333,
    );

    $formatted = $progress->format();

    expect($formatted)->toContain('33.3%');
});
