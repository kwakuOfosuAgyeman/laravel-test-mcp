<?php

namespace Kwaku\LaravelTestMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Process;
use Kwaku\LaravelTestMcp\Concerns\HasRateLimiting;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListTestsTool extends Tool
{
    use HasRateLimiting;

    protected string $name = 'list_tests';
    
    protected string $description = 'List all available tests without running them. Useful for discovering what tests exist.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('Directory to scan for tests')
                ->default('tests'),
            
            'format' => $schema->string()
                ->enum(['tree', 'flat', 'json'])
                ->description('Output format')
                ->default('tree'),
        ];
    }

    public function handle(Request $request): Response
    {
        if ($rateLimitResponse = $this->checkRateLimit()) {
            return $rateLimitResponse;
        }

        $path = $request->get('path', 'tests');
        $format = $request->get('format', 'tree');
        
        $process = Process::path(base_path())
            ->run(['./vendor/bin/pest', '--list-tests', $path]);
        
        if (!$process->successful()) {
            // Try PHPUnit
            $process = Process::path(base_path())
                ->run(['./vendor/bin/phpunit', '--list-tests', $path]);
        }
        
        $tests = $this->parseTestList($process->output());
        
        return Response::text(
            $this->formatOutput($tests, $format)
        );
    }
    
    private function parseTestList(string $output): array
    {
        // Parse the --list-tests output into structured data
        $tests = [];
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            if (preg_match('/^\s*-\s*(.+)::(.+)$/', $line, $matches)) {
                $class = $matches[1];
                $method = $matches[2];
                
                if (!isset($tests[$class])) {
                    $tests[$class] = [];
                }
                $tests[$class][] = $method;
            }
        }
        
        return $tests;
    }
    
    private function formatOutput(array $tests, string $format): string
    {
        if ($format === 'json') {
            return json_encode($tests, JSON_PRETTY_PRINT);
        }
        
        if ($format === 'flat') {
            $output = [];
            foreach ($tests as $class => $methods) {
                foreach ($methods as $method) {
                    $output[] = "{$class}::{$method}";
                }
            }
            return implode("\n", $output);
        }
        
        // Tree format (default)
        $output = ["# Available Tests\n"];
        foreach ($tests as $class => $methods) {
            $output[] = "## {$class}";
            foreach ($methods as $method) {
                $output[] = "  - {$method}";
            }
            $output[] = '';
        }
        
        return implode("\n", $output);
    }
}