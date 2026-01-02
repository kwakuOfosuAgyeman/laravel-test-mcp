<?php

namespace Kwaku\LaravelTestMcp\DTOs;

readonly class CoverageResult
{
    /**
     * @param array<string, array{
     *     percentage: float,
     *     covered_lines: array<int>,
     *     uncovered_lines: array<int>
     * }> $files
     */
    public function __construct(
        public float $percentage,
        public int $totalLines,
        public int $coveredLines,
        public array $files = [],
    ) {}
}
