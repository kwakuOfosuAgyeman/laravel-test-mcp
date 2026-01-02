<?php

namespace Kwaku\LaravelTestMcp\Resources;

use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Response;
use Illuminate\Support\Facades\File;

class TestHistoryResource extends Resource
{
    protected string $uri = 'test://history';
    
    protected string $name = 'Test Run History';
    
    protected string $description = 'Historical test results showing trends over time, flaky test detection, and performance changes.';
    
    protected string $mimeType = 'application/json';

    public function read(): Response
    {
        $historyDir = storage_path('test-mcp/history');
        
        if (!is_dir($historyDir)) {
            return Response::text(json_encode([
                'status' => 'no_history',
                'message' => 'No test history found. Run tests multiple times to build history.',
                'runs' => [],
            ]));
        }
        
        // Get last 50 runs
        $files = File::glob($historyDir . '/*.json');
        rsort($files); // Most recent first
        $files = array_slice($files, 0, 50);
        
        $runs = [];
        $flakyTests = [];
        $slowTests = [];
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            $runs[] = [
                'timestamp' => $data['timestamp'],
                'passed' => $data['passed'],
                'total' => $data['totalCount'],
                'failed' => $data['failedCount'],
                'duration' => $data['duration'],
            ];
            
            // Track flaky tests (tests that sometimes pass, sometimes fail)
            foreach ($data['failures'] ?? [] as $failure) {
                $testName = $failure['test'];
                if (!isset($flakyTests[$testName])) {
                    $flakyTests[$testName] = ['passed' => 0, 'failed' => 0];
                }
                $flakyTests[$testName]['failed']++;
            }
            
            foreach ($data['passedTests'] ?? [] as $test) {
                $testName = $test['name'];
                if (!isset($flakyTests[$testName])) {
                    $flakyTests[$testName] = ['passed' => 0, 'failed' => 0];
                }
                $flakyTests[$testName]['passed']++;
                
                // Track slow tests
                if ($test['duration'] > 1.0) {
                    if (!isset($slowTests[$testName])) {
                        $slowTests[$testName] = [];
                    }
                    $slowTests[$testName][] = $test['duration'];
                }
            }
        }
        
        // Filter to actually flaky tests (both passed and failed at least once)
        $actuallyFlaky = array_filter($flakyTests, fn($stats) => 
            $stats['passed'] > 0 && $stats['failed'] > 0
        );
        
        // Calculate average duration for slow tests
        $slowTestsSummary = [];
        foreach ($slowTests as $test => $durations) {
            $slowTestsSummary[$test] = [
                'avg_duration' => round(array_sum($durations) / count($durations), 3),
                'max_duration' => round(max($durations), 3),
                'occurrences' => count($durations),
            ];
        }
        
        // Sort by average duration
        uasort($slowTestsSummary, fn($a, $b) => $b['avg_duration'] <=> $a['avg_duration']);
        
        return Response::text(json_encode([
            'status' => 'ok',
            'total_runs' => count($runs),
            'success_rate' => $this->calculateSuccessRate($runs),
            'avg_duration' => $this->calculateAvgDuration($runs),
            'flaky_tests' => array_map(fn($stats, $name) => [
                'test' => $name,
                'flakiness' => round($stats['failed'] / ($stats['passed'] + $stats['failed']) * 100, 1) . '%',
                'passed' => $stats['passed'],
                'failed' => $stats['failed'],
            ], $actuallyFlaky, array_keys($actuallyFlaky)),
            'slow_tests' => array_slice($slowTestsSummary, 0, 10, true),
            'recent_runs' => array_slice($runs, 0, 10),
        ], JSON_PRETTY_PRINT));
    }
    
    private function calculateSuccessRate(array $runs): string
    {
        if (empty($runs)) return '0%';
        
        $passed = count(array_filter($runs, fn($r) => $r['passed']));
        return round($passed / count($runs) * 100, 1) . '%';
    }
    
    private function calculateAvgDuration(array $runs): float
    {
        if (empty($runs)) return 0;
        
        return round(
            array_sum(array_column($runs, 'duration')) / count($runs),
            2
        );
    }
}