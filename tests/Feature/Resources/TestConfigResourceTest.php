<?php

use Kwaku\LaravelTestMcp\Resources\TestConfigResource;
use Laravel\Mcp\Response;

beforeEach(function () {
    $this->resource = app(TestConfigResource::class);
});

test('TestConfigResource returns a response', function () {
    $result = $this->resource->read();

    expect($result)->toBeInstanceOf(Response::class);
});

test('TestConfigResource can read config without errors', function () {
    // This test just verifies the resource doesn't throw exceptions
    $result = $this->resource->read();

    expect($result)->toBeInstanceOf(Response::class);
});
