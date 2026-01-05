<?php

namespace Kwaku\LaravelTestMcp\Services;

use InvalidArgumentException;
use Kwaku\LaravelTestMcp\DTOs\ClassAnalysis;
use Kwaku\LaravelTestMcp\DTOs\MethodInfo;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class ClassAnalyzer
{
    private string $projectRoot;

    public function __construct()
    {
        $this->projectRoot = realpath(base_path()) ?: base_path();
    }

    public function analyze(string $classPath): ClassAnalysis
    {
        $classPath = $this->validatePath($classPath);
        $fullPath = $this->projectRoot . '/' . ltrim($classPath, '/');

        if (!file_exists($fullPath)) {
            throw new InvalidArgumentException("File not found: {$classPath}");
        }

        if (pathinfo($fullPath, PATHINFO_EXTENSION) !== 'php') {
            throw new InvalidArgumentException('Only PHP files can be analyzed');
        }

        $className = $this->extractClassName($fullPath);

        if ($className === null) {
            throw new InvalidArgumentException('Could not determine class name from file');
        }

        // Ensure the class is loaded
        if (!class_exists($className) && !interface_exists($className) && !trait_exists($className)) {
            require_once $fullPath;
        }

        if (!class_exists($className)) {
            throw new InvalidArgumentException("Class {$className} not found after loading file");
        }

        $reflection = new ReflectionClass($className);
        $type = $this->detectType($reflection);

        return new ClassAnalysis(
            className: $className,
            shortName: $reflection->getShortName(),
            namespace: $reflection->getNamespaceName(),
            type: $type,
            parentClass: $reflection->getParentClass() ? $reflection->getParentClass()->getName() : null,
            methods: $this->extractMethods($reflection),
            dependencies: $this->extractDependencies($reflection),
            laravelFeatures: $this->detectLaravelFeatures($reflection, $type),
            filePath: $classPath,
            traits: $reflection->getTraitNames(),
            interfaces: $reflection->getInterfaceNames(),
        );
    }

    private function validatePath(?string $path): string
    {
        if ($path === null || $path === '') {
            throw new InvalidArgumentException('Path is required');
        }

        // Remove any null bytes
        $path = str_replace("\0", '', $path);

        // Normalize path separators
        $path = str_replace('\\', '/', $path);

        // Check for path traversal attempts
        if (preg_match('/\.\./', $path)) {
            throw new InvalidArgumentException('Path traversal not allowed');
        }

        // Check for shell metacharacters
        if (preg_match('/[;&|`$(){}[\]<>!]/', $path)) {
            throw new InvalidArgumentException('Invalid characters in path');
        }

        // Ensure path is within project
        $fullPath = $this->projectRoot . '/' . ltrim($path, '/');
        $realPath = realpath($fullPath);

        if ($realPath === false) {
            // File might not exist yet, check parent
            $parentPath = realpath(dirname($fullPath));
            if ($parentPath === false || !str_starts_with($parentPath, $this->projectRoot)) {
                throw new InvalidArgumentException('Path must be within project directory');
            }
        } elseif (!str_starts_with($realPath, $this->projectRoot)) {
            throw new InvalidArgumentException('Path must be within project directory');
        }

        return $path;
    }

    private function extractClassName(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);

        // Extract namespace
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = trim($matches[1]);
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)/', $contents, $matches)) {
            $className = trim($matches[1]);
            return $namespace ? "{$namespace}\\{$className}" : $className;
        }

        return null;
    }

    private function detectType(ReflectionClass $reflection): string
    {
        // Check inheritance chain for Laravel base classes
        if ($this->extendsClass($reflection, 'Illuminate\Database\Eloquent\Model')) {
            return 'model';
        }

        if ($this->extendsClass($reflection, 'Illuminate\Foundation\Http\FormRequest')) {
            return 'formrequest';
        }

        if ($this->extendsClass($reflection, 'Illuminate\Routing\Controller') ||
            $this->extendsClass($reflection, 'App\Http\Controllers\Controller')) {
            return 'controller';
        }

        // Check interfaces
        if ($reflection->implementsInterface('Illuminate\Contracts\Queue\ShouldQueue')) {
            return 'job';
        }

        // Check by file path conventions
        $filePath = $reflection->getFileName();

        if (str_contains($filePath, '/Http/Controllers/') || str_contains($filePath, '/Controllers/')) {
            return 'controller';
        }

        if (str_contains($filePath, '/Http/Middleware/') || str_contains($filePath, '/Middleware/')) {
            return 'middleware';
        }

        if (str_contains($filePath, '/Listeners/')) {
            return 'listener';
        }

        if (str_contains($filePath, '/Jobs/')) {
            return 'job';
        }

        if (str_contains($filePath, '/Services/')) {
            return 'service';
        }

        if (str_contains($filePath, '/Repositories/')) {
            return 'repository';
        }

        if (str_contains($filePath, '/Actions/')) {
            return 'action';
        }

        return 'class';
    }

    private function extendsClass(ReflectionClass $reflection, string $parentClass): bool
    {
        $current = $reflection;
        while ($current = $current->getParentClass()) {
            if ($current->getName() === $parentClass) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<MethodInfo>
     */
    private function extractMethods(ReflectionClass $reflection): array
    {
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip inherited methods (only analyze declared methods)
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            // Skip magic methods except __construct
            if (str_starts_with($method->getName(), '__') && $method->getName() !== '__construct') {
                continue;
            }

            $methods[] = new MethodInfo(
                name: $method->getName(),
                visibility: 'public',
                isStatic: $method->isStatic(),
                returnType: $this->extractReturnTypeName($method),
                returnTypeNullable: $method->hasReturnType() && $method->getReturnType()->allowsNull(),
                parameters: $this->extractParameters($method),
                docblock: $method->getDocComment() ?: null,
                line: $method->getStartLine(),
            );
        }

        return $methods;
    }

    private function extractReturnTypeName(ReflectionMethod $method): ?string
    {
        if (!$method->hasReturnType()) {
            return null;
        }

        $type = $method->getReturnType();

        if ($type instanceof ReflectionUnionType) {
            $types = array_map(fn($t) => $t->getName(), $type->getTypes());
            return implode('|', $types);
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    /**
     * @return array<array{name: string, type: ?string, nullable: bool, hasDefault: bool, default: mixed}>
     */
    private function extractParameters(ReflectionMethod $method): array
    {
        $params = [];

        foreach ($method->getParameters() as $param) {
            $params[] = [
                'name' => $param->getName(),
                'type' => $this->getParameterTypeName($param),
                'nullable' => $param->allowsNull(),
                'hasDefault' => $param->isDefaultValueAvailable(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ];
        }

        return $params;
    }

    private function getParameterTypeName(ReflectionParameter $param): ?string
    {
        if (!$param->hasType()) {
            return null;
        }

        $type = $param->getType();

        if ($type instanceof ReflectionUnionType) {
            $types = array_map(fn($t) => $t->getName(), $type->getTypes());
            return implode('|', $types);
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    /**
     * @return array<string>
     */
    private function extractDependencies(ReflectionClass $reflection): array
    {
        if (!$reflection->hasMethod('__construct')) {
            return [];
        }

        $constructor = $reflection->getMethod('__construct');
        $dependencies = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $type->getName();
            }
        }

        return $dependencies;
    }

    /**
     * @return array<string, mixed>
     */
    private function detectLaravelFeatures(ReflectionClass $reflection, string $type): array
    {
        $features = [];

        switch ($type) {
            case 'model':
                $features = $this->detectModelFeatures($reflection);
                break;
            case 'controller':
                $features = $this->detectControllerFeatures($reflection);
                break;
            case 'formrequest':
                $features = $this->detectFormRequestFeatures($reflection);
                break;
            case 'job':
                $features = $this->detectJobFeatures($reflection);
                break;
            case 'middleware':
                $features = $this->detectMiddlewareFeatures($reflection);
                break;
        }

        return $features;
    }

    /**
     * @return array<string, mixed>
     */
    private function detectModelFeatures(ReflectionClass $reflection): array
    {
        $features = [
            'relationships' => [],
            'scopes' => [],
            'accessors' => [],
            'mutators' => [],
            'fillable' => [],
            'casts' => [],
            'hidden' => [],
        ];

        // Detect relationships, scopes, accessors, mutators from method names
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            // Scopes: scope{Name}
            if (preg_match('/^scope(.+)$/', $name, $matches)) {
                $features['scopes'][] = lcfirst($matches[1]);
            }

            // Accessors: get{Attribute}Attribute (Laravel 8 style)
            if (preg_match('/^get(.+)Attribute$/', $name, $matches)) {
                $features['accessors'][] = lcfirst($matches[1]);
            }

            // Mutators: set{Attribute}Attribute (Laravel 8 style)
            if (preg_match('/^set(.+)Attribute$/', $name, $matches)) {
                $features['mutators'][] = lcfirst($matches[1]);
            }

            // Relationships: methods that return relationship types
            $returnType = $this->extractReturnTypeName($method);
            if ($returnType && $this->isRelationshipReturnType($returnType)) {
                $features['relationships'][] = [
                    'name' => $name,
                    'type' => $this->getRelationshipType($returnType),
                ];
            }
        }

        // Try to get property values
        try {
            $instance = $reflection->newInstanceWithoutConstructor();

            if ($reflection->hasProperty('fillable')) {
                $prop = $reflection->getProperty('fillable');
                $prop->setAccessible(true);
                $features['fillable'] = $prop->getValue($instance) ?? [];
            }

            if ($reflection->hasProperty('casts')) {
                $prop = $reflection->getProperty('casts');
                $prop->setAccessible(true);
                $features['casts'] = $prop->getValue($instance) ?? [];
            }

            if ($reflection->hasProperty('hidden')) {
                $prop = $reflection->getProperty('hidden');
                $prop->setAccessible(true);
                $features['hidden'] = $prop->getValue($instance) ?? [];
            }
        } catch (\Throwable) {
            // Can't instantiate, skip property extraction
        }

        return $features;
    }

    private function isRelationshipReturnType(string $type): bool
    {
        $relationshipTypes = [
            'Illuminate\Database\Eloquent\Relations\HasOne',
            'Illuminate\Database\Eloquent\Relations\HasMany',
            'Illuminate\Database\Eloquent\Relations\BelongsTo',
            'Illuminate\Database\Eloquent\Relations\BelongsToMany',
            'Illuminate\Database\Eloquent\Relations\HasOneThrough',
            'Illuminate\Database\Eloquent\Relations\HasManyThrough',
            'Illuminate\Database\Eloquent\Relations\MorphOne',
            'Illuminate\Database\Eloquent\Relations\MorphMany',
            'Illuminate\Database\Eloquent\Relations\MorphTo',
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
        ];

        foreach ($relationshipTypes as $relType) {
            if (str_contains($type, class_basename($relType)) || $type === $relType) {
                return true;
            }
        }

        return false;
    }

    private function getRelationshipType(string $type): string
    {
        // Extract just the relationship type name
        $baseName = class_basename($type);
        return match ($baseName) {
            'HasOne' => 'hasOne',
            'HasMany' => 'hasMany',
            'BelongsTo' => 'belongsTo',
            'BelongsToMany' => 'belongsToMany',
            'HasOneThrough' => 'hasOneThrough',
            'HasManyThrough' => 'hasManyThrough',
            'MorphOne' => 'morphOne',
            'MorphMany' => 'morphMany',
            'MorphTo' => 'morphTo',
            'MorphToMany' => 'morphToMany',
            default => $baseName,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function detectControllerFeatures(ReflectionClass $reflection): array
    {
        $features = [
            'middleware' => [],
            'resourceActions' => [],
            'routes' => [],
        ];

        // Detect resource-style actions
        $resourceMethods = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (in_array($method->getName(), $resourceMethods)) {
                $features['resourceActions'][] = $method->getName();
            }
        }

        // Try to find routes for this controller
        try {
            $routes = app('router')->getRoutes();
            $controllerName = $reflection->getName();

            foreach ($routes as $route) {
                $action = $route->getAction('uses');
                if (is_string($action) && str_contains($action, $controllerName)) {
                    $features['routes'][] = [
                        'uri' => $route->uri(),
                        'methods' => $route->methods(),
                        'name' => $route->getName(),
                        'action' => str_replace($controllerName . '@', '', $action),
                        'middleware' => $route->middleware(),
                    ];
                }
            }
        } catch (\Throwable) {
            // Routes not available
        }

        return $features;
    }

    /**
     * @return array<string, mixed>
     */
    private function detectFormRequestFeatures(ReflectionClass $reflection): array
    {
        $features = [
            'rules' => [],
            'messages' => [],
            'hasAuthorize' => false,
        ];

        // Check if authorize method is overridden
        if ($reflection->hasMethod('authorize')) {
            $method = $reflection->getMethod('authorize');
            if ($method->getDeclaringClass()->getName() === $reflection->getName()) {
                $features['hasAuthorize'] = true;
            }
        }

        // Try to get rules by instantiating
        try {
            $instance = app($reflection->getName());

            if (method_exists($instance, 'rules')) {
                $features['rules'] = $instance->rules();
            }

            if (method_exists($instance, 'messages')) {
                $features['messages'] = $instance->messages();
            }
        } catch (\Throwable) {
            // Can't instantiate FormRequest outside of request context
        }

        return $features;
    }

    /**
     * @return array<string, mixed>
     */
    private function detectJobFeatures(ReflectionClass $reflection): array
    {
        $features = [
            'hasHandle' => $reflection->hasMethod('handle'),
            'isQueueable' => $reflection->implementsInterface('Illuminate\Contracts\Queue\ShouldQueue'),
            'traits' => [],
        ];

        // Check for common job traits
        $jobTraits = [
            'Illuminate\Bus\Queueable',
            'Illuminate\Queue\InteractsWithQueue',
            'Illuminate\Foundation\Bus\Dispatchable',
            'Illuminate\Queue\SerializesModels',
        ];

        foreach ($reflection->getTraitNames() as $trait) {
            if (in_array($trait, $jobTraits)) {
                $features['traits'][] = class_basename($trait);
            }
        }

        return $features;
    }

    /**
     * @return array<string, mixed>
     */
    private function detectMiddlewareFeatures(ReflectionClass $reflection): array
    {
        $features = [
            'hasHandle' => $reflection->hasMethod('handle'),
            'hasTerminate' => $reflection->hasMethod('terminate'),
        ];

        return $features;
    }
}
