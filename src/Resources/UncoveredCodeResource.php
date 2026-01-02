<?php

namespace Kwaku\LaravelTestMcp\Resources;

use Laravel\Mcp\Server\ResourceTemplate;
use Laravel\Mcp\Response;

class UncoveredCodeResource extends ResourceTemplate
{
    protected string $uriTemplate = 'test://coverage/uncovered/{file}';
    
    protected string $name = 'Uncovered Code';
    
    protected string $description = 'Show the actual uncovered code snippets for a specific file, helping you write tests for untested code.';
    
    protected string $mimeType = 'application/json';

    public function read(array $params): Response
    {
        $requestedFile = $params['file'] ?? '';
        
        $coveragePath = storage_path('test-mcp/coverage-detailed.json');
        
        if (!file_exists($coveragePath)) {
            return Response::error('No coverage data. Run get_coverage tool first.');
        }
        
        $coverage = json_decode(file_get_contents($coveragePath), true);
        
        // Find matching file
        $matchedFile = null;
        $fileData = null;
        
        foreach ($coverage['files'] as $file => $data) {
            if (str_contains($file, $requestedFile)) {
                $matchedFile = $file;
                $fileData = $data;
                break;
            }
        }
        
        if (!$matchedFile) {
            return Response::error("File not found in coverage data: {$requestedFile}");
        }
        
        if (empty($fileData['uncovered_lines'])) {
            return Response::text(json_encode([
                'file' => $matchedFile,
                'status' => 'fully_covered',
                'message' => 'This file is fully covered!',
            ]));
        }
        
        // Read the actual file and extract uncovered snippets
        if (!file_exists($matchedFile)) {
            return Response::error("Source file not found: {$matchedFile}");
        }
        
        $sourceLines = file($matchedFile);
        $snippets = $this->extractUncoveredSnippets(
            $sourceLines,
            $fileData['uncovered_lines']
        );
        
        return Response::text(json_encode([
            'file' => $matchedFile,
            'coverage_percentage' => $fileData['percentage'],
            'uncovered_line_count' => count($fileData['uncovered_lines']),
            'snippets' => $snippets,
        ], JSON_PRETTY_PRINT));
    }
    
    private function extractUncoveredSnippets(array $sourceLines, array $uncoveredLines): array
    {
        $snippets = [];
        $currentSnippet = null;
        
        sort($uncoveredLines);
        
        foreach ($uncoveredLines as $lineNum) {
            $index = $lineNum - 1; // Arrays are 0-indexed
            
            if (!isset($sourceLines[$index])) {
                continue;
            }
            
            // Start new snippet or extend current one
            if ($currentSnippet === null || $lineNum > $currentSnippet['end_line'] + 3) {
                // Save previous snippet
                if ($currentSnippet !== null) {
                    $snippets[] = $currentSnippet;
                }
                
                // Start new snippet with context (2 lines before)
                $startLine = max(1, $lineNum - 2);
                $currentSnippet = [
                    'start_line' => $startLine,
                    'end_line' => $lineNum,
                    'uncovered_lines' => [$lineNum],
                    'code' => '',
                ];
            } else {
                // Extend current snippet
                $currentSnippet['end_line'] = $lineNum;
                $currentSnippet['uncovered_lines'][] = $lineNum;
            }
        }
        
        // Don't forget last snippet
        if ($currentSnippet !== null) {
            $snippets[] = $currentSnippet;
        }
        
        // Build code strings with context
        foreach ($snippets as &$snippet) {
            // Add 2 lines after for context
            $snippet['end_line'] = min(count($sourceLines), $snippet['end_line'] + 2);
            
            $codeLines = [];
            for ($i = $snippet['start_line']; $i <= $snippet['end_line']; $i++) {
                $prefix = in_array($i, $snippet['uncovered_lines']) ? '> ' : '  ';
                $codeLines[] = sprintf('%s%4d: %s', $prefix, $i, rtrim($sourceLines[$i - 1]));
            }
            $snippet['code'] = implode("\n", $codeLines);
        }
        
        return array_slice($snippets, 0, 10); // Limit to 10 snippets
    }
}