<?php

namespace Kwaku\LaravelTestMcp\Services;

use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use Kwaku\LaravelTestMcp\DTOs\TestResult;

class TestRunner
{
    private string $projectRoot;
    private string $testFramework; // 'pest' or 'phpunit'

    public function __construct()
    {
        $this->projectRoot = base_path();
        $this->testFramework = $this->detectFramework();
    }

    public function run(
        ?string $path = null,
        ?string $filter = null,
        ?string $group = null,
        ?string $excludeGroup = null,
        bool $stopOnFailure = false,
        bool $parallel = false,
    ): TestResult {
        // Validate and sanitize inputs
        $path = $this->validatePath($path);
        $filter = $this->sanitizeFilter($filter);
        $group = $this->sanitizeGroup($group);
        $excludeGroup = $this->sanitizeGroup($excludeGroup);

        $command = $this->buildCommand(
            path: $path,
            filter: $filter,
            group: $group,
            excludeGroup: $excludeGroup,
            stopOnFailure: $stopOnFailure,
            parallel: $parallel,
        );

        $timeout = (int) config('test-mcp.timeout', 300);

        $process = Process::path($this->projectRoot)
            ->timeout($timeout)
            ->run($command);

        return $this->parseOutput(
            stdout: $process->output(),
            stderr: $process->errorOutput(),
            exitCode: $process->exitCode(),
        );
    }

    private function validatePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        // Remove any null bytes
        $path = str_replace("\0", '', $path);

        // Normalize path separators
        $path = str_replace('\\', '/', $path);

        // Check for path traversal attempts
        if (preg_match('/\.\./', $path)) {
            throw new InvalidArgumentException('Path traversal not allowed');
        }

        // Check for shell metacharacters
        if (preg_match('/[;&|`$(){}[\]<>!]/', $path)) {
            throw new InvalidArgumentException('Invalid characters in path');
        }

        // Ensure path is within project
        $fullPath = $this->projectRoot . '/' . ltrim($path, '/');
        $realPath = realpath($fullPath);

        // For new paths that don't exist yet, validate the parent directory
        if ($realPath === false) {
            $parentPath = realpath(dirname($fullPath));
            if ($parentPath === false || !str_starts_with($parentPath, $this->projectRoot)) {
                throw new InvalidArgumentException('Path must be within project directory');
            }
        } elseif (!str_starts_with($realPath, $this->projectRoot)) {
            throw new InvalidArgumentException('Path must be within project directory');
        }

        return $path;
    }

    private function sanitizeFilter(?string $filter): ?string
    {
        if ($filter === null) {
            return null;
        }

        // Remove null bytes
        $filter = str_replace("\0", '', $filter);

        // Check for shell metacharacters (allow basic regex chars)
        if (preg_match('/[;&|`$(){}[\]<>!]/', $filter)) {
            throw new InvalidArgumentException('Invalid characters in filter');
        }

        // Limit length
        if (strlen($filter) > 200) {
            throw new InvalidArgumentException('Filter too long');
        }

        return $filter;
    }

    private function sanitizeGroup(?string $group): ?string
    {
        if ($group === null) {
            return null;
        }

        // Remove null bytes
        $group = str_replace("\0", '', $group);

        // Groups should only contain alphanumeric, dash, underscore, comma
        if (!preg_match('/^[\w\-,]+$/', $group)) {
            throw new InvalidArgumentException('Invalid characters in group name');
        }

        // Limit length
        if (strlen($group) > 200) {
            throw new InvalidArgumentException('Group name too long');
        }

        return $group;
    }

    private function detectFramework(): string
    {
        // Check config first
        $configured = config('test-mcp.framework');
        if ($configured && in_array($configured, ['pest', 'phpunit'])) {
            return $configured;
        }

        // Check for Pest first (it's built on PHPUnit)
        if (file_exists($this->projectRoot . '/vendor/bin/pest')) {
            return 'pest';
        }

        return 'phpunit';
    }

    private function buildCommand(
        ?string $path,
        ?string $filter,
        ?string $group,
        ?string $excludeGroup,
        bool $stopOnFailure,
        bool $parallel,
    ): array {
        $binary = $this->testFramework === 'pest'
            ? './vendor/bin/pest'
            : './vendor/bin/phpunit';

        $command = [$binary];

        // Use JSON output for easier parsing (PHPUnit 10+)
        $command[] = '--log-junit=storage/test-results.xml';

        // Add TeamCity format for real-time parsing
        $command[] = '--teamcity';

        if ($path) {
            $command[] = $path;
        }

        if ($filter) {
            $command[] = "--filter={$filter}";
        }

        if ($group) {
            $command[] = "--group={$group}";
        }

        if ($excludeGroup) {
            $command[] = "--exclude-group={$excludeGroup}";
        }

        if ($stopOnFailure) {
            $command[] = '--stop-on-failure';
        }

        if ($parallel && $this->testFramework === 'pest') {
            $command[] = '--parallel';
        }

        return $command;
    }
    
    private function parseOutput(string $stdout, string $stderr, int $exitCode): TestResult
    {
        $parser = app(OutputParser::class);
        
        // Try to parse JUnit XML first (most structured)
        $xmlPath = $this->projectRoot . '/storage/test-results.xml';
        if (file_exists($xmlPath)) {
            $result = $parser->parseJunitXml(file_get_contents($xmlPath));
            @unlink($xmlPath); // Clean up
            return $result;
        }
        
        // Fall back to TeamCity format parsing
        return $parser->parseTeamCity($stdout, $exitCode);
    }
}