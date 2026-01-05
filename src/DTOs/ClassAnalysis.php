<?php

namespace Kwaku\LaravelTestMcp\DTOs;

readonly class ClassAnalysis
{
    /**
     * @param array<MethodInfo> $methods
     * @param array<string> $dependencies Constructor-injected class names
     * @param array<string, mixed> $laravelFeatures Type-specific features (relationships, scopes, rules, etc.)
     * @param array<string> $traits
     * @param array<string> $interfaces
     */
    public function __construct(
        public string $className,
        public string $shortName,
        public string $namespace,
        public string $type,
        public ?string $parentClass,
        public array $methods,
        public array $dependencies,
        public array $laravelFeatures,
        public string $filePath,
        public array $traits = [],
        public array $interfaces = [],
    ) {}

    public function isController(): bool
    {
        return $this->type === 'controller';
    }

    public function isModel(): bool
    {
        return $this->type === 'model';
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function isFormRequest(): bool
    {
        return $this->type === 'formrequest';
    }

    public function isJob(): bool
    {
        return $this->type === 'job';
    }

    public function isMiddleware(): bool
    {
        return $this->type === 'middleware';
    }

    public function isListener(): bool
    {
        return $this->type === 'listener';
    }
}
