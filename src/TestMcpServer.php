<?php

namespace Kwaku\LaravelTestMcp;

use Laravel\Mcp\Server\Server;
use Kwaku\LaravelTestMcp\Tools\{
    RunTestsTool,
    ListTestsTool,
    GetCoverageTool,
    WatchTestsTool,
    MutationTestTool,
    CancelOperationTool,
    GenerateTestTool,
};
use Kwaku\LaravelTestMcp\Resources\{
    TestResultsResource,
    CoverageReportResource,
    TestFileResource,
    TestConfigResource,
    TestHistoryResource,
    UncoveredCodeResource,
};
use Kwaku\LaravelTestMcp\Prompts\{
    TddWorkflowPrompt,
    DebugFailingTestPrompt,
    AnalyzeCoveragePrompt,
};

class TestMcpServer extends Server
{
    protected string $name = 'Laravel Test Runner';

    protected string $version = '1.1.0';

    protected string $description = 'Run and analyze Pest/PHPUnit tests via MCP';

    protected array $tools = [
        // Core tools
        RunTestsTool::class,
        ListTestsTool::class,
        GetCoverageTool::class,
        CancelOperationTool::class,

        // Advanced tools
        WatchTestsTool::class,
        MutationTestTool::class,

        // Test generation
        GenerateTestTool::class,
    ];

    protected array $resources = [
        // Core resources
        TestResultsResource::class,      // test://results/latest
        CoverageReportResource::class,   // test://coverage/summary
        TestFileResource::class,         // test://files/{path}
        TestConfigResource::class,       // test://config

        // Advanced resources
        TestHistoryResource::class,      // test://history
        UncoveredCodeResource::class,    // test://coverage/uncovered/{file}
    ];

    protected array $prompts = [
        TddWorkflowPrompt::class,        // tdd_workflow
        DebugFailingTestPrompt::class,   // debug_failing_test
        AnalyzeCoveragePrompt::class,    // analyze_coverage
    ];
}
