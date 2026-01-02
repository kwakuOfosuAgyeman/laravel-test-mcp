<?php

use Illuminate\Support\Facades\Cache;
use Kwaku\LaravelTestMcp\Services\CancellationToken;

beforeEach(function () {
    Cache::flush();
});

test('CancellationToken generates unique operation IDs', function () {
    $token1 = CancellationToken::create();
    $token2 = CancellationToken::create();

    expect($token1->getOperationId())->not->toBe($token2->getOperationId());
});

test('CancellationToken operation ID has correct format', function () {
    $token = CancellationToken::create();

    expect($token->getOperationId())->toStartWith('op_');
    expect(strlen($token->getOperationId()))->toBeGreaterThan(5);
});

test('CancellationToken is not cancelled by default', function () {
    $token = CancellationToken::create();

    expect($token->isCancelled())->toBeFalse();
});

test('CancellationToken can be cancelled', function () {
    $token = CancellationToken::create();

    $token->cancel();

    expect($token->isCancelled())->toBeTrue();
});

test('CancellationToken cancellation persists across instances', function () {
    $token1 = CancellationToken::create();
    $operationId = $token1->getOperationId();

    $token1->cancel();

    $token2 = CancellationToken::forOperation($operationId);
    expect($token2->isCancelled())->toBeTrue();
});

test('CancellationToken can be reset', function () {
    $token = CancellationToken::create();

    $token->cancel();
    expect($token->isCancelled())->toBeTrue();

    $token->reset();
    expect($token->isCancelled())->toBeFalse();
});

test('CancellationToken forOperation retrieves existing token', function () {
    $operationId = 'op_test123abc';

    $token = CancellationToken::forOperation($operationId);
    expect($token->getOperationId())->toBe($operationId);
});

test('CancellationToken different operations are independent', function () {
    $token1 = CancellationToken::create();
    $token2 = CancellationToken::create();

    $token1->cancel();

    expect($token1->isCancelled())->toBeTrue();
    expect($token2->isCancelled())->toBeFalse();
});
