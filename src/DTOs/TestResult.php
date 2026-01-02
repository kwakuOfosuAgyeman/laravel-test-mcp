<?php

namespace Kwaku\LaravelTestMcp\DTOs;

readonly class TestResult
{
    /**
     * @param array<TestFailure> $failures
     * @param array<PassedTest> $passedTests
     */
    public function __construct(
        public bool $passed,
        public int $totalCount,
        public int $passedCount,
        public int $failedCount,
        public float $duration,
        public array $failures = [],
        public array $passedTests = [],
    ) {}
}
