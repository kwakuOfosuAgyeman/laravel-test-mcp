<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Test Framework
    |--------------------------------------------------------------------------
    |
    | The test framework to use. Auto-detected if null.
    | Options: 'pest', 'phpunit', null (auto-detect)
    |
    */
    'framework' => env('TEST_MCP_FRAMEWORK'),

    /*
    |--------------------------------------------------------------------------
    | Default Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds to wait for test execution.
    |
    */
    'timeout' => env('TEST_MCP_TIMEOUT', 300),

    /*
    |--------------------------------------------------------------------------
    | Coverage Driver
    |--------------------------------------------------------------------------
    |
    | The coverage driver to use. Options: 'xdebug', 'pcov', null (auto-detect)
    |
    */
    'coverage_driver' => env('TEST_MCP_COVERAGE_DRIVER'),

    /*
    |--------------------------------------------------------------------------
    | Confirmation Threshold
    |--------------------------------------------------------------------------
    |
    | When running tests, if the test count exceeds this threshold, the tool
    | will ask for confirmation (via force: true) before running. Set to 0
    | to disable this check.
    |
    */
    'confirmation_threshold' => env('TEST_MCP_CONFIRMATION_THRESHOLD', 50),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent abuse and resource exhaustion.
    |
    */
    'rate_limit' => [
        // Enable or disable rate limiting
        'enabled' => env('TEST_MCP_RATE_LIMIT_ENABLED', true),

        // Maximum number of requests allowed within the decay period
        'max_attempts' => env('TEST_MCP_RATE_LIMIT_MAX_ATTEMPTS', 60),

        // Time window in seconds for rate limiting
        'decay_seconds' => env('TEST_MCP_RATE_LIMIT_DECAY_SECONDS', 60),
    ],
];