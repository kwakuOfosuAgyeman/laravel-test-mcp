<?php

namespace Kwaku\LaravelTestMcp\Tests;

use Kwaku\LaravelTestMcp\LaravelTestMcpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelTestMcpServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('test-mcp.framework', null);
        $app['config']->set('test-mcp.timeout', 300);
        $app['config']->set('test-mcp.coverage_driver', null);
        $app['config']->set('test-mcp.rate_limit.enabled', true);
        $app['config']->set('test-mcp.rate_limit.max_attempts', 60);
        $app['config']->set('test-mcp.rate_limit.decay_seconds', 60);
    }
}
