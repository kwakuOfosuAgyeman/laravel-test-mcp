<?php

namespace Kwaku\LaravelTestMcp\Services;

use Kwaku\LaravelTestMcp\DTOs\{TestResult, TestFailure, PassedTest};

class OutputParser
{
    public function parseJunitXml(string $xml): TestResult
    {
        $doc = new \SimpleXMLElement($xml);
        
        $totalCount = 0;
        $passedCount = 0;
        $failures = [];
        $passedTests = [];
        $duration = 0;
        
        foreach ($doc->testsuite as $suite) {
            foreach ($suite->testcase as $testcase) {
                $totalCount++;
                $testName = (string) $testcase['name'];
                $testClass = (string) $testcase['class'];
                $testTime = (float) $testcase['time'];
                $duration += $testTime;
                
                $failure = $testcase->failure ?? $testcase->error ?? null;
                
                if ($failure) {
                    $failures[] = new TestFailure(
                        test: "{$testClass}::{$testName}",
                        file: (string) $testcase['file'],
                        line: (int) $testcase['line'],
                        message: (string) $failure,
                        diff: $this->extractDiff((string) $failure),
                    );
                } else {
                    $passedCount++;
                    $passedTests[] = new PassedTest(
                        name: "{$testClass}::{$testName}",
                        duration: $testTime,
                    );
                }
            }
        }
        
        return new TestResult(
            passed: empty($failures),
            totalCount: $totalCount,
            passedCount: $passedCount,
            failedCount: count($failures),
            duration: round($duration, 3),
            failures: $failures,
            passedTests: $passedTests,
        );
    }
    
    private function extractDiff(string $message): ?string
    {
        // Extract diff from assertion messages
        if (preg_match('/---\s*Expected.*?\+\+\+\s*Actual.*$/s', $message, $matches)) {
            return $matches[0];
        }
        return null;
    }

    public function parseTeamCity(string $output, int $exitCode): TestResult
    {
        $totalCount = 0;
        $passedCount = 0;
        $failures = [];
        $passedTests = [];
        $duration = 0.0;

        // TeamCity format: ##teamcity[testStarted name='testName']
        // ##teamcity[testFinished name='testName' duration='123']
        // ##teamcity[testFailed name='testName' message='error' details='stack trace']

        $lines = explode("\n", $output);
        $currentTest = null;
        $testStartTimes = [];

        foreach ($lines as $line) {
            // Parse testStarted
            if (preg_match("/##teamcity\[testStarted name='([^']+)'/", $line, $matches)) {
                $currentTest = $matches[1];
                $testStartTimes[$currentTest] = microtime(true);
                $totalCount++;
            }

            // Parse testFailed
            if (preg_match("/##teamcity\[testFailed name='([^']+)' message='([^']*)'/", $line, $matches)) {
                $testName = $matches[1];
                $message = $this->unescapeTeamCity($matches[2]);

                // Extract details if present
                $details = '';
                if (preg_match("/details='([^']*)'/", $line, $detailMatches)) {
                    $details = $this->unescapeTeamCity($detailMatches[1]);
                }

                // Try to extract file and line from message or details
                $file = '';
                $lineNum = 0;
                if (preg_match('/([^\s]+\.php):(\d+)/', $details . $message, $locationMatches)) {
                    $file = $locationMatches[1];
                    $lineNum = (int) $locationMatches[2];
                }

                $failures[] = new TestFailure(
                    test: $testName,
                    file: $file,
                    line: $lineNum,
                    message: $message,
                    diff: $this->extractDiff($details),
                );
            }

            // Parse testFinished
            if (preg_match("/##teamcity\[testFinished name='([^']+)' duration='(\d+)'/", $line, $matches)) {
                $testName = $matches[1];
                $testDuration = (float) $matches[2] / 1000; // Convert ms to seconds
                $duration += $testDuration;

                // Check if this test didn't fail
                $failed = false;
                foreach ($failures as $failure) {
                    if ($failure->test === $testName) {
                        $failed = true;
                        break;
                    }
                }

                if (!$failed) {
                    $passedCount++;
                    $passedTests[] = new PassedTest(
                        name: $testName,
                        duration: $testDuration,
                    );
                }
            }
        }

        return new TestResult(
            passed: empty($failures) && $exitCode === 0,
            totalCount: $totalCount,
            passedCount: $passedCount,
            failedCount: count($failures),
            duration: round($duration, 3),
            failures: $failures,
            passedTests: $passedTests,
        );
    }

    private function unescapeTeamCity(string $value): string
    {
        // TeamCity escapes: |' |n |r |x |] ||
        return str_replace(
            ["|'", '|n', '|r', '|x', '|]', '||'],
            ["'", "\n", "\r", '', ']', '|'],
            $value
        );
    }
}