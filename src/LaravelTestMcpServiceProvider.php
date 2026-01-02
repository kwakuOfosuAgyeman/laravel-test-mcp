<?php

namespace Kwaku\LaravelTestMcp;

use Illuminate\Support\ServiceProvider;
use Kwaku\LaravelTestMcp\Services\CoverageAnalyzer;
use Kwaku\LaravelTestMcp\Services\OutputParser;
use Kwaku\LaravelTestMcp\Services\RateLimiter;
use Kwaku\LaravelTestMcp\Services\TestRunner;

class LaravelTestMcpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/test-mcp.php', 'test-mcp');
        
        $this->app->singleton(TestRunner::class);
        $this->app->singleton(OutputParser::class);
        $this->app->singleton(CoverageAnalyzer::class);
        $this->app->singleton(RateLimiter::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/test-mcp.php' => config_path('test-mcp.php'),
            ], 'test-mcp-config');
        }
        
        $this->loadRoutesFrom(__DIR__ . '/../routes/ai.php');
    }
}