<?php

namespace Kwaku\LaravelTestMcp\DTOs;

readonly class PassedTest
{
    public function __construct(
        public string $name,
        public float $duration,
    ) {}
}
