<?php

namespace Kwaku\LaravelTestMcp\Resources;

use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Response;

class CoverageReportResource extends Resource
{
    protected string $uri = 'test://coverage/summary';
    
    protected string $name = 'Code Coverage Summary';
    
    protected string $description = 'Current code coverage statistics by file and overall percentage.';
    
    protected string $mimeType = 'application/json';

    public function read(): Response
    {
        $coveragePath = storage_path('test-mcp/coverage-summary.json');
        
        if (!file_exists($coveragePath)) {
            return Response::text(json_encode([
                'status' => 'no_coverage',
                'message' => 'No coverage data found. Run get_coverage tool first.',
            ]));
        }
        
        $coverage = json_decode(file_get_contents($coveragePath), true);
        
        return Response::text(json_encode([
            'overall_percentage' => $coverage['percentage'],
            'total_lines' => $coverage['totalLines'],
            'covered_lines' => $coverage['coveredLines'],
            'timestamp' => $coverage['timestamp'],
            'files' => collect($coverage['files'])
                ->map(fn($data, $file) => [
                    'file' => $file,
                    'percentage' => $data['percentage'],
                    'uncovered_count' => count($data['uncovered_lines']),
                ])
                ->sortBy('percentage')
                ->values()
                ->all(),
        ], JSON_PRETTY_PRINT));
    }
}