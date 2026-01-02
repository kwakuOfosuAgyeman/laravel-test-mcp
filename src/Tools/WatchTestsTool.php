<?php

namespace Kwaku\LaravelTestMcp\Tools;

use Generator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Kwaku\LaravelTestMcp\DTOs\ProgressUpdate;
use Kwaku\LaravelTestMcp\Services\CancellationToken;
use Kwaku\LaravelTestMcp\Services\RateLimiter;
use Kwaku\LaravelTestMcp\Services\TestRunner;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class WatchTestsTool extends Tool
{
    protected string $name = 'watch_tests';

    protected string $description = 'Watch files and automatically re-run related tests on changes. Perfect for TDD workflow. Supports cancellation via operation ID.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('Directory to watch')
                ->default('app'),

            'test_path' => $schema->string()
                ->description('Test directory')
                ->default('tests'),

            'duration' => $schema->integer()
                ->description('How long to watch (seconds). Max 300.')
                ->default(60)
                ->maximum(300),
        ];
    }

    public function handle(Request $request): Generator
    {
        $rateLimiter = app(RateLimiter::class);
        if (!$rateLimiter->attempt('tool:watch_tests')) {
            $decaySeconds = config('test-mcp.rate_limit.decay_seconds', 60);
            yield Response::error(
                "Rate limit exceeded. Too many requests. Please wait {$decaySeconds} seconds before trying again."
            );
            return;
        }

        // Create cancellation token
        $token = CancellationToken::create();
        $operationId = $token->getOperationId();

        $path = $request->input('path', 'app');
        $testPath = $request->input('test_path', 'tests');
        $duration = min($request->input('duration', 60), 300);

        $endTime = time() + $duration;
        $lastModified = $this->getLastModified($path);
        $filesWatched = $this->countPhpFiles($path);
        $testsRun = 0;
        $testsPassed = 0;
        $testsFailed = 0;

        yield Response::text("🚀 Watch session started (ID: {$operationId})");
        yield Response::text("👀 Watching {$path} ({$filesWatched} PHP files) for {$duration}s");
        yield Response::text("💡 Use `cancel_operation` with ID `{$operationId}` to stop early");

        try {
            while (time() < $endTime) {
                // Check for cancellation
                if ($token->isCancelled()) {
                    yield Response::text("\n❌ Watch session cancelled by user");
                    break;
                }

                $elapsed = time() - ($endTime - $duration);
                $remaining = $endTime - time();

                // Progress update every 10 seconds
                if ($elapsed % 10 === 0 && $elapsed > 0) {
                    $progress = new ProgressUpdate(
                        stage: 'Watching',
                        completed: $elapsed,
                        total: $duration,
                        percentComplete: ($elapsed / $duration) * 100,
                        currentItem: "{$remaining}s remaining",
                        operationId: $operationId,
                    );
                    yield Response::text($progress->format());
                }

                $currentModified = $this->getLastModified($path);

                if ($currentModified > $lastModified) {
                    $changedFile = $this->findChangedFile($path, $lastModified);
                    $lastModified = $currentModified;

                    yield Response::text("\n📝 Change detected: {$changedFile}");

                    // Find and run related test
                    $relatedTest = $this->findRelatedTest($changedFile, $testPath);

                    if ($relatedTest) {
                        yield Response::text("🧪 Running: {$relatedTest}");

                        $result = app(TestRunner::class)->run(path: $relatedTest);
                        $testsRun++;

                        if ($result->passed) {
                            $testsPassed++;
                            yield Response::text("✅ PASSED in {$result->duration}s");
                        } else {
                            $testsFailed++;
                            yield Response::text("❌ FAILED in {$result->duration}s");

                            // Show first failure details
                            if (!empty($result->failures)) {
                                $failure = $result->failures[0];
                                yield Response::text("   └─ {$failure->message}");
                            }
                        }
                    } else {
                        yield Response::text("⚠️ No related test found, running all tests...");
                        $result = app(TestRunner::class)->run();
                        $testsRun++;

                        if ($result->passed) {
                            $testsPassed++;
                        } else {
                            $testsFailed++;
                        }

                        yield Response::text("Completed: {$result->passedCount}/{$result->totalCount} passed");
                    }
                }

                sleep(1);
            }

            // Summary
            yield Response::text("\n" . str_repeat("─", 40));
            yield Response::text("## Watch Session Summary");
            yield Response::text("**Duration:** {$duration}s");
            yield Response::text("**Test runs:** {$testsRun}");
            yield Response::text("**Passed:** {$testsPassed}");
            yield Response::text("**Failed:** {$testsFailed}");
            yield Response::text("⏱️ Watch session ended.");

        } finally {
            $token->reset();
        }
    }

    private function countPhpFiles(string $path): int
    {
        $fullPath = base_path($path);

        if (!is_dir($fullPath)) {
            return 0;
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $count++;
            }
        }

        return $count;
    }

    private function findRelatedTest(string $file, string $testPath): ?string
    {
        // app/Models/User.php -> tests/Unit/Models/UserTest.php
        // app/Http/Controllers/UserController.php -> tests/Feature/Http/Controllers/UserControllerTest.php

        $relativePath = str_replace(base_path() . '/', '', $file);

        // Try common patterns
        $patterns = [
            // app/Models/User.php -> tests/Unit/Models/UserTest.php
            str_replace('app/', 'tests/Unit/', str_replace('.php', 'Test.php', $relativePath)),
            // app/X/Y.php -> tests/Feature/X/YTest.php
            str_replace('app/', 'tests/Feature/', str_replace('.php', 'Test.php', $relativePath)),
        ];

        foreach ($patterns as $pattern) {
            $fullPath = base_path($pattern);
            if (file_exists($fullPath)) {
                return $pattern;
            }
        }

        return null;
    }

    private function getLastModified(string $path): int
    {
        $fullPath = base_path($path);

        if (!is_dir($fullPath)) {
            return 0;
        }

        $lastModified = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $mtime = $file->getMTime();
                if ($mtime > $lastModified) {
                    $lastModified = $mtime;
                }
            }
        }

        return $lastModified;
    }

    private function findChangedFile(string $path, int $since): ?string
    {
        $fullPath = base_path($path);

        if (!is_dir($fullPath)) {
            return null;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                if ($file->getMTime() > $since) {
                    return $file->getPathname();
                }
            }
        }

        return null;
    }
}
