<?php

namespace Kwaku\LaravelTestMcp\Tools;

use Generator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Process;
use Kwaku\LaravelTestMcp\Concerns\HasRateLimiting;
use Kwaku\LaravelTestMcp\DTOs\ProgressUpdate;
use Kwaku\LaravelTestMcp\DTOs\TestResult;
use Kwaku\LaravelTestMcp\Services\CancellationToken;
use Kwaku\LaravelTestMcp\Services\TestRunner;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class RunTestsTool extends Tool
{
    use HasRateLimiting;

    protected string $name = 'run_tests';

    protected string $description = 'Run Pest or PHPUnit tests. Returns test results with pass/fail status, execution time, and failure details. Supports progress tracking and cancellation.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('File or directory path to test (relative to project root). Examples: "tests/Unit/UserTest.php", "tests/Feature"')
                ->nullable(),

            'filter' => $schema->string()
                ->description('Filter tests by name. Supports regex. Examples: "test_user_can_login", "/user/i"')
                ->nullable(),

            'group' => $schema->string()
                ->description('Run only tests in specific group(s). Comma-separated for multiple.')
                ->nullable(),

            'exclude_group' => $schema->string()
                ->description('Exclude tests in specific group(s).')
                ->nullable(),

            'stop_on_failure' => $schema->boolean()
                ->description('Stop execution on first failure')
                ->default(false),

            'parallel' => $schema->boolean()
                ->description('Run tests in parallel (Pest only)')
                ->default(false),

            'dry_run' => $schema->boolean()
                ->description('Preview what tests would run without executing them')
                ->default(false),

            'force' => $schema->boolean()
                ->description('Skip confirmation prompt for large test suites')
                ->default(false),
        ];
    }

    public function handle(Request $request): Generator
    {
        if ($rateLimitResponse = $this->checkRateLimit()) {
            yield $rateLimitResponse;
            return;
        }

        $validated = $request->validate([
            'path' => 'nullable|string|max:500',
            'filter' => 'nullable|string|max:200',
            'group' => 'nullable|string|max:200',
            'exclude_group' => 'nullable|string|max:200',
            'stop_on_failure' => 'boolean',
            'parallel' => 'boolean',
            'dry_run' => 'boolean',
            'force' => 'boolean',
        ]);

        // Create cancellation token
        $token = CancellationToken::create();
        $operationId = $token->getOperationId();

        yield Response::text("🚀 Operation started (ID: {$operationId})");

        // Check for cancellation
        if ($token->isCancelled()) {
            yield Response::text("❌ Operation cancelled");
            return;
        }

        $path = $validated['path'] ?? null;
        $dryRun = $validated['dry_run'] ?? false;
        $force = $validated['force'] ?? false;

        // Discover tests first
        yield Response::text("🔍 Discovering tests...");

        $testCount = $this->countTests($path);

        if ($token->isCancelled()) {
            yield Response::text("❌ Operation cancelled");
            return;
        }

        // Check confirmation threshold
        $threshold = (int) config('test-mcp.confirmation_threshold', 50);
        if ($testCount > $threshold && !$force && !$dryRun) {
            yield Response::text("⚠️ Found {$testCount} tests (threshold: {$threshold})");
            yield Response::text("Use `force: true` to run all tests, or `dry_run: true` to preview");
            return;
        }

        // Dry run mode
        if ($dryRun) {
            yield Response::text($this->formatDryRun($testCount, $path));
            return;
        }

        // Report progress
        $progress = new ProgressUpdate(
            stage: 'Running tests',
            completed: 0,
            total: $testCount,
            percentComplete: 0,
            operationId: $operationId,
        );
        yield Response::text($progress->format());

        $runner = app(TestRunner::class);

        try {
            $result = $runner->run(
                path: $path,
                filter: $validated['filter'] ?? null,
                group: $validated['group'] ?? null,
                excludeGroup: $validated['exclude_group'] ?? null,
                stopOnFailure: $validated['stop_on_failure'] ?? false,
                parallel: $validated['parallel'] ?? false,
            );

            // Check for cancellation after execution
            if ($token->isCancelled()) {
                yield Response::text("❌ Operation cancelled (results may be partial)");
            }

            // Final progress
            $progress = new ProgressUpdate(
                stage: 'Complete',
                completed: $result->totalCount,
                total: $result->totalCount,
                percentComplete: 100,
                operationId: $operationId,
            );
            yield Response::text($progress->format());

            yield Response::text($this->formatResult($result));

        } catch (\Exception $e) {
            yield Response::error("Test execution failed: {$e->getMessage()}");
        } finally {
            // Clean up token
            $token->reset();
        }
    }

    private function countTests(?string $path): int
    {
        $targetPath = $path ?? 'tests';

        // Try Pest first
        $process = Process::path(base_path())
            ->timeout(30)
            ->run(['./vendor/bin/pest', '--list-tests', $targetPath]);

        if (!$process->successful()) {
            // Try PHPUnit
            $process = Process::path(base_path())
                ->timeout(30)
                ->run(['./vendor/bin/phpunit', '--list-tests', $targetPath]);
        }

        $output = $process->output();
        $count = 0;

        // Count lines that look like test methods
        foreach (explode("\n", $output) as $line) {
            if (preg_match('/^\s*-\s*.+::.+$/', $line)) {
                $count++;
            }
        }

        return max($count, 1); // At least 1 to avoid division by zero
    }

    private function formatDryRun(int $testCount, ?string $path): string
    {
        $output = [];
        $output[] = "## Dry Run Preview\n";
        $output[] = "**Tests found:** {$testCount}";
        $output[] = "**Path:** " . ($path ?? 'tests/ (all)');
        $output[] = "";
        $output[] = "Run without `dry_run: true` to execute these tests.";

        return implode("\n", $output);
    }

    private function formatResult(TestResult $result): string
    {
        $output = [];

        // Summary line
        $status = $result->passed ? '✅ PASSED' : '❌ FAILED';
        $output[] = "{$status} | {$result->passedCount}/{$result->totalCount} tests | {$result->duration}s";
        $output[] = '';

        // Failures (if any)
        if (!empty($result->failures)) {
            $output[] = '## Failures';
            foreach ($result->failures as $failure) {
                $output[] = "### {$failure->test}";
                $output[] = "File: {$failure->file}:{$failure->line}";
                $output[] = "Message: {$failure->message}";
                if ($failure->diff) {
                    $output[] = "```diff";
                    $output[] = $failure->diff;
                    $output[] = "```";
                }
                $output[] = '';
            }
        }

        // Passed tests summary
        if ($result->passedCount > 0) {
            $output[] = '## Passed Tests';
            foreach ($result->passedTests as $test) {
                $output[] = "- {$test->name} ({$test->duration}s)";
            }
        }

        return implode("\n", $output);
    }
}
