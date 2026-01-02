<?php

namespace Kwaku\LaravelTestMcp\Resources;

use Laravel\Mcp\Server\ResourceTemplate;
use Laravel\Mcp\Response;

class TestFileResource extends ResourceTemplate
{
    protected string $uriTemplate = 'test://files/{path}';
    
    protected string $name = 'Test File Contents';
    
    protected string $description = 'Read the contents of a specific test file. Path is relative to tests/ directory.';
    
    protected string $mimeType = 'text/x-php';

    public function read(array $params): Response
    {
        $path = $params['path'] ?? '';
        
        // Security: Ensure path stays within tests directory
        $fullPath = base_path('tests/' . $path);
        $realPath = realpath($fullPath);
        $testsDir = realpath(base_path('tests'));
        
        if (!$realPath || !str_starts_with($realPath, $testsDir)) {
            return Response::error('Invalid path: must be within tests/ directory');
        }
        
        if (!file_exists($realPath)) {
            return Response::error("Test file not found: {$path}");
        }
        
        if (!str_ends_with($realPath, '.php')) {
            return Response::error('Can only read PHP test files');
        }
        
        $contents = file_get_contents($realPath);
        
        return Response::text($contents);
    }
    
    /**
     * Provide completions for the path parameter
     */
    public function completeArguments(string $argumentName, string $currentValue): array
    {
        if ($argumentName !== 'path') {
            return [];
        }
        
        $testsDir = base_path('tests');
        $files = $this->findTestFiles($testsDir, $currentValue);
        
        return array_map(fn($file) => [
            'value' => $file,
            'description' => "Test file: {$file}",
        ], $files);
    }
    
    private function findTestFiles(string $dir, string $prefix = ''): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($dir . '/', '', $file->getPathname());
                
                if (empty($prefix) || str_starts_with($relativePath, $prefix)) {
                    $files[] = $relativePath;
                }
            }
        }
        
        return array_slice($files, 0, 20); // Limit for performance
    }
}