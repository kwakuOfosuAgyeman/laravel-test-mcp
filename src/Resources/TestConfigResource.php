<?php

namespace Kwaku\LaravelTestMcp\Resources;

use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Response;

class TestConfigResource extends Resource
{
    protected string $uri = 'test://config';
    
    protected string $name = 'Test Configuration';
    
    protected string $description = 'Current PHPUnit/Pest configuration including test suites, environment settings, and coverage configuration.';
    
    protected string $mimeType = 'application/json';

    public function read(): Response
    {
        $config = [
            'framework' => $this->detectFramework(),
            'config_file' => null,
            'suites' => [],
            'environment' => [],
            'coverage' => [],
        ];
        
        // Parse phpunit.xml or phpunit.xml.dist
        $phpunitPath = base_path('phpunit.xml');
        if (!file_exists($phpunitPath)) {
            $phpunitPath = base_path('phpunit.xml.dist');
        }
        
        if (file_exists($phpunitPath)) {
            $config['config_file'] = basename($phpunitPath);
            $config = array_merge($config, $this->parsePhpunitXml($phpunitPath));
        }
        
        // Check for Pest configuration
        $pestPath = base_path('tests/Pest.php');
        if (file_exists($pestPath)) {
            $config['pest_config'] = 'tests/Pest.php';
            $config['pest_uses'] = $this->parsePestUses($pestPath);
        }
        
        return Response::text(json_encode($config, JSON_PRETTY_PRINT));
    }
    
    private function detectFramework(): string
    {
        if (file_exists(base_path('vendor/bin/pest'))) {
            return 'pest';
        }
        return 'phpunit';
    }
    
    private function parsePhpunitXml(string $path): array
    {
        $xml = simplexml_load_file($path);
        $config = [];
        
        // Parse test suites
        $config['suites'] = [];
        if (isset($xml->testsuites->testsuite)) {
            foreach ($xml->testsuites->testsuite as $suite) {
                $suiteName = (string) $suite['name'];
                $config['suites'][$suiteName] = [];
                
                foreach ($suite->directory as $dir) {
                    $config['suites'][$suiteName][] = (string) $dir;
                }
            }
        }
        
        // Parse environment variables
        $config['environment'] = [];
        if (isset($xml->php->env)) {
            foreach ($xml->php->env as $env) {
                $config['environment'][(string) $env['name']] = (string) $env['value'];
            }
        }
        
        // Parse coverage configuration
        if (isset($xml->coverage) || isset($xml->source)) {
            $config['coverage'] = [
                'enabled' => true,
                'include' => [],
                'exclude' => [],
            ];
            
            $source = $xml->source ?? $xml->coverage;
            if (isset($source->include->directory)) {
                foreach ($source->include->directory as $dir) {
                    $config['coverage']['include'][] = (string) $dir;
                }
            }
        }
        
        return $config;
    }
    
    private function parsePestUses(string $path): array
    {
        $contents = file_get_contents($path);
        $uses = [];
        
        // Extract uses() calls
        if (preg_match_all('/uses\(([^)]+)\)/s', $contents, $matches)) {
            foreach ($matches[1] as $match) {
                // Extract class names
                if (preg_match_all('/([A-Z][a-zA-Z0-9_\\\\]+)::class/', $match, $classes)) {
                    $uses = array_merge($uses, $classes[1]);
                }
            }
        }
        
        return array_unique($uses);
    }
}