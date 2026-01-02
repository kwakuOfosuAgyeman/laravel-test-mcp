<?php

namespace Kwaku\LaravelTestMcp\Resources;

use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Response;

class TestResultsResource extends Resource
{
    protected string $uri = 'test://results/latest';
    
    protected string $name = 'Latest Test Results';
    
    protected string $description = 'The most recent test run results, including pass/fail status and failure details.';
    
    protected string $mimeType = 'application/json';

    public function read(): Response
    {
        $resultsPath = storage_path('test-mcp/latest-results.json');
        
        if (!file_exists($resultsPath)) {
            return Response::text(json_encode([
                'status' => 'no_results',
                'message' => 'No test results found. Run tests first using the run_tests tool.',
            ]));
        }
        
        $results = json_decode(file_get_contents($resultsPath), true);
        
        return Response::text(json_encode([
            'status' => $results['passed'] ? 'passed' : 'failed',
            'summary' => [
                'total' => $results['totalCount'],
                'passed' => $results['passedCount'],
                'failed' => $results['failedCount'],
                'duration' => $results['duration'],
                'timestamp' => $results['timestamp'],
            ],
            'failures' => array_map(fn($f) => [
                'test' => $f['test'],
                'file' => $f['file'],
                'line' => $f['line'],
                'message' => $f['message'],
            ], $results['failures'] ?? []),
        ], JSON_PRETTY_PRINT));
    }
}