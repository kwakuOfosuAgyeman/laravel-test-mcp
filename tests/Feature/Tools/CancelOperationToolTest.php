<?php

use Kwaku\LaravelTestMcp\Services\CancellationToken;
use Kwaku\LaravelTestMcp\Tools\CancelOperationTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function () {
    $this->tool = app(CancelOperationTool::class);
});

test('CancelOperationTool returns a response', function () {
    $request = new Request(['operation_id' => 'op_test123abc']);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('CancelOperationTool cancels an operation', function () {
    $token = CancellationToken::create();
    $operationId = $token->getOperationId();

    expect($token->isCancelled())->toBeFalse();

    $request = new Request(['operation_id' => $operationId]);
    $this->tool->handle($request);

    // Verify it was actually cancelled
    $checkToken = CancellationToken::forOperation($operationId);
    expect($checkToken->isCancelled())->toBeTrue();
});

test('CancelOperationTool handles already cancelled operations', function () {
    $token = CancellationToken::create();
    $operationId = $token->getOperationId();
    $token->cancel();

    $request = new Request(['operation_id' => $operationId]);
    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});

test('CancelOperationTool handles invalid operation ID format', function () {
    $request = new Request(['operation_id' => 'invalid-format']);

    $result = $this->tool->handle($request);

    expect($result)->toBeInstanceOf(Response::class);
});
