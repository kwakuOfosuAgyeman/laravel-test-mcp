<?php

namespace Kwaku\LaravelTestMcp\Tools;

use Generator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Kwaku\LaravelTestMcp\Concerns\HasRateLimiting;
use Kwaku\LaravelTestMcp\DTOs\CoverageResult;
use Kwaku\LaravelTestMcp\DTOs\ProgressUpdate;
use Kwaku\LaravelTestMcp\Services\CancellationToken;
use Kwaku\LaravelTestMcp\Services\CoverageAnalyzer;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetCoverageTool extends Tool
{
    use HasRateLimiting;

    protected string $name = 'get_coverage';

    protected string $description = 'Run tests with code coverage and return coverage report. Shows which lines/methods are tested. Supports progress tracking.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('Path to test or directory')
                ->nullable(),

            'filter_file' => $schema->string()
                ->description('Only show coverage for specific file. Example: "app/Models/User.php"')
                ->nullable(),

            'min_coverage' => $schema->integer()
                ->description('Fail if coverage is below this percentage')
                ->minimum(0)
                ->maximum(100)
                ->nullable(),

            'format' => $schema->string()
                ->enum(['summary', 'detailed', 'uncovered'])
                ->description('Output format: summary (percentages only), detailed (line-by-line), uncovered (only show uncovered lines)')
                ->default('summary'),

            'dry_run' => $schema->boolean()
                ->description('Preview what would be analyzed without running coverage')
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
            'path' => 'nullable|string',
            'filter_file' => 'nullable|string',
            'min_coverage' => 'nullable|integer|min:0|max:100',
            'format' => 'in:summary,detailed,uncovered',
            'dry_run' => 'boolean',
        ]);

        // Create cancellation token
        $token = CancellationToken::create();
        $operationId = $token->getOperationId();

        yield Response::text("🚀 Coverage analysis started (ID: {$operationId})");

        $dryRun = $validated['dry_run'] ?? false;

        if ($dryRun) {
            yield Response::text($this->formatDryRun($validated));
            return;
        }

        // Check for cancellation
        if ($token->isCancelled()) {
            yield Response::text("❌ Operation cancelled");
            return;
        }

        // Progress: Checking coverage driver
        $progress = new ProgressUpdate(
            stage: 'Checking coverage driver',
            completed: 1,
            total: 3,
            percentComplete: 33.3,
            operationId: $operationId,
        );
        yield Response::text($progress->format());

        $analyzer = app(CoverageAnalyzer::class);

        // Progress: Running tests with coverage
        $progress = new ProgressUpdate(
            stage: 'Running tests with coverage',
            completed: 2,
            total: 3,
            percentComplete: 66.6,
            currentItem: 'This may take a while...',
            operationId: $operationId,
        );
        yield Response::text($progress->format());

        if ($token->isCancelled()) {
            yield Response::text("❌ Operation cancelled");
            return;
        }

        try {
            $result = $analyzer->run(
                path: $validated['path'] ?? null,
                filterFile: $validated['filter_file'] ?? null,
            );

            // Progress: Complete
            $progress = new ProgressUpdate(
                stage: 'Analysis complete',
                completed: 3,
                total: 3,
                percentComplete: 100,
                operationId: $operationId,
            );
            yield Response::text($progress->format());

            // Check minimum coverage
            if (isset($validated['min_coverage'])) {
                if ($result->percentage < $validated['min_coverage']) {
                    yield Response::error(
                        "Coverage {$result->percentage}% is below minimum {$validated['min_coverage']}%"
                    );
                    return;
                }
            }

            yield Response::text(
                $this->formatCoverage($result, $validated['format'] ?? 'summary')
            );

        } catch (\Exception $e) {
            yield Response::error("Coverage analysis failed: {$e->getMessage()}");
        } finally {
            $token->reset();
        }
    }

    private function formatDryRun(array $validated): string
    {
        $output = [];
        $output[] = "## Coverage Dry Run Preview\n";
        $output[] = "**Path:** " . ($validated['path'] ?? 'tests/ (all)');

        if (isset($validated['filter_file'])) {
            $output[] = "**Filter file:** {$validated['filter_file']}";
        }

        if (isset($validated['min_coverage'])) {
            $output[] = "**Minimum coverage:** {$validated['min_coverage']}%";
        }

        $output[] = "**Format:** " . ($validated['format'] ?? 'summary');
        $output[] = "";
        $output[] = "⚠️ Coverage analysis requires Xdebug or PCOV extension.";
        $output[] = "";
        $output[] = "Run without `dry_run: true` to execute coverage analysis.";

        return implode("\n", $output);
    }

    private function formatCoverage(CoverageResult $result, string $format): string
    {
        $output = [];
        $output[] = "# Code Coverage Report";
        $output[] = "";
        $output[] = "**Overall: {$result->percentage}%** ({$result->coveredLines}/{$result->totalLines} lines)";
        $output[] = "";

        if ($format === 'summary') {
            $output[] = "## By File";
            foreach ($result->files as $file => $data) {
                $emoji = $data['percentage'] >= 80 ? '✅' : ($data['percentage'] >= 50 ? '⚠️' : '❌');
                $output[] = "{$emoji} {$file}: {$data['percentage']}%";
            }
            return implode("\n", $output);
        }

        if ($format === 'uncovered') {
            $output[] = "## Uncovered Lines";
            foreach ($result->files as $file => $data) {
                if (!empty($data['uncovered_lines'])) {
                    $output[] = "### {$file}";
                    $output[] = "Lines: " . implode(', ', $data['uncovered_lines']);
                    $output[] = "";
                }
            }
            return implode("\n", $output);
        }

        // Detailed format
        foreach ($result->files as $file => $data) {
            $output[] = "## {$file} ({$data['percentage']}%)";
            $output[] = "Covered: " . implode(', ', $data['covered_lines']);
            $output[] = "Uncovered: " . implode(', ', $data['uncovered_lines']);
            $output[] = "";
        }

        return implode("\n", $output);
    }
}
