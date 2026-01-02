<?php

use Laravel\Mcp\Facades\Mcp;
use Kwaku\LaravelTestMcp\TestMcpServer;

// Local server for CLI/IDE integration (most common use case)
Mcp::local('test-runner', TestMcpServer::class);

// Optional: HTTP server for remote access
// Mcp::web('/mcp/tests', TestMcpServer::class)
//     ->middleware(['auth:sanctum']);