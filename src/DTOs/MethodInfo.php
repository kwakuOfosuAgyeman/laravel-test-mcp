<?php

namespace Kwaku\LaravelTestMcp\DTOs;

readonly class MethodInfo
{
    /**
     * @param array<array{name: string, type: ?string, nullable: bool, hasDefault: bool, default: mixed}> $parameters
     */
    public function __construct(
        public string $name,
        public string $visibility,
        public bool $isStatic,
        public ?string $returnType,
        public bool $returnTypeNullable,
        public array $parameters = [],
        public ?string $docblock = null,
        public int $line = 0,
    ) {}
}
