<?php

use Kwaku\LaravelTestMcp\DTOs\CoverageResult;

test('CoverageResult can be instantiated with basic properties', function () {
    $result = new CoverageResult(
        percentage: 85.5,
        totalLines: 1000,
        coveredLines: 855,
    );

    expect($result->percentage)->toBe(85.5);
    expect($result->totalLines)->toBe(1000);
    expect($result->coveredLines)->toBe(855);
    expect($result->files)->toBeEmpty();
});

test('CoverageResult can include file breakdown', function () {
    $files = [
        '/app/Models/User.php' => [
            'percentage' => 90.0,
            'covered_lines' => [1, 2, 3, 5, 6, 7, 8, 9, 10],
            'uncovered_lines' => [4],
        ],
        '/app/Models/Post.php' => [
            'percentage' => 75.0,
            'covered_lines' => [1, 2, 3],
            'uncovered_lines' => [4],
        ],
    ];

    $result = new CoverageResult(
        percentage: 82.5,
        totalLines: 14,
        coveredLines: 12,
        files: $files,
    );

    expect($result->files)->toHaveCount(2);
    expect($result->files['/app/Models/User.php']['percentage'])->toBe(90.0);
    expect($result->files['/app/Models/Post.php']['percentage'])->toBe(75.0);
});

test('CoverageResult handles zero coverage', function () {
    $result = new CoverageResult(
        percentage: 0.0,
        totalLines: 100,
        coveredLines: 0,
    );

    expect($result->percentage)->toBe(0.0);
    expect($result->coveredLines)->toBe(0);
});

test('CoverageResult handles full coverage', function () {
    $result = new CoverageResult(
        percentage: 100.0,
        totalLines: 500,
        coveredLines: 500,
    );

    expect($result->percentage)->toBe(100.0);
    expect($result->totalLines)->toBe($result->coveredLines);
});
