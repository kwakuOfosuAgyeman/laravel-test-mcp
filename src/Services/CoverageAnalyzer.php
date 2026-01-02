<?php

namespace Kwaku\LaravelTestMcp\Services;

use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use Kwaku\LaravelTestMcp\DTOs\CoverageResult;

class CoverageAnalyzer
{
    private string $projectRoot;

    public function __construct()
    {
        $this->projectRoot = base_path();
    }

    public function run(?string $path = null, ?string $filterFile = null): CoverageResult
    {
        // Validate path if provided
        if ($path !== null) {
            $path = $this->validatePath($path);
        }

        $cloverPath = storage_path('coverage/clover.xml');

        // Ensure directory exists
        if (!is_dir(dirname($cloverPath))) {
            mkdir(dirname($cloverPath), 0755, true);
        }

        $binary = $this->detectBinary();

        $command = [
            $binary,
            '--coverage-clover=' . $cloverPath,
        ];

        if ($path) {
            $command[] = $path;
        }

        $timeout = (int) config('test-mcp.timeout', 300);

        $process = Process::path($this->projectRoot)
            ->timeout($timeout * 2) // Coverage takes longer
            ->run($command);

        if (!file_exists($cloverPath)) {
            throw new \RuntimeException(
                "Coverage report not generated. Ensure Xdebug or PCOV is installed. Error: " . $process->errorOutput()
            );
        }

        return $this->parseClover($cloverPath, $filterFile);
    }

    private function validatePath(string $path): string
    {
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

        if ($realPath !== false && !str_starts_with($realPath, $this->projectRoot)) {
            throw new InvalidArgumentException('Path must be within project directory');
        }

        return $path;
    }

    private function detectBinary(): string
    {
        // Check config first
        $configured = config('test-mcp.framework');
        if ($configured === 'phpunit') {
            return './vendor/bin/phpunit';
        }
        if ($configured === 'pest') {
            return './vendor/bin/pest';
        }

        // Auto-detect: prefer Pest if available
        if (file_exists($this->projectRoot . '/vendor/bin/pest')) {
            return './vendor/bin/pest';
        }

        return './vendor/bin/phpunit';
    }
    
    private function parseClover(string $path, ?string $filterFile): CoverageResult
    {
        $xml = new \SimpleXMLElement(file_get_contents($path));
        
        $files = [];
        $totalLines = 0;
        $coveredLines = 0;
        
        foreach ($xml->project->package as $package) {
            foreach ($package->file as $file) {
                $fileName = (string) $file['name'];
                
                // Filter if requested
                if ($filterFile && !str_contains($fileName, $filterFile)) {
                    continue;
                }
                
                $fileData = [
                    'covered_lines' => [],
                    'uncovered_lines' => [],
                ];
                
                foreach ($file->line as $line) {
                    $lineNum = (int) $line['num'];
                    $hits = (int) $line['count'];
                    
                    $totalLines++;
                    
                    if ($hits > 0) {
                        $coveredLines++;
                        $fileData['covered_lines'][] = $lineNum;
                    } else {
                        $fileData['uncovered_lines'][] = $lineNum;
                    }
                }
                
                $total = count($fileData['covered_lines']) + count($fileData['uncovered_lines']);
                $fileData['percentage'] = $total > 0 
                    ? round(count($fileData['covered_lines']) / $total * 100, 1)
                    : 0;
                
                $files[$fileName] = $fileData;
            }
        }
        
        return new CoverageResult(
            percentage: $totalLines > 0 ? round($coveredLines / $totalLines * 100, 1) : 0,
            totalLines: $totalLines,
            coveredLines: $coveredLines,
            files: $files,
        );
    }
}