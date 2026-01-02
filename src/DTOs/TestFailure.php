<?php

namespace Kwaku\LaravelTestMcp\DTOs;

readonly class TestFailure
{
    public function __construct(
        public string $test,
        public string $file,
        public int $line,
        public string $message,
        public ?string $diff = null,
    ) {}
}
