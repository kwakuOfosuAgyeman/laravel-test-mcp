<?php

namespace Kwaku\LaravelTestMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Kwaku\LaravelTestMcp\Concerns\HasRateLimiting;
use Kwaku\LaravelTestMcp\Services\ClassAnalyzer;
use Kwaku\LaravelTestMcp\Services\TestGenerator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GenerateTestTool extends Tool
{
    use HasRateLimiting;

    protected string $name = 'generate_test';

    protected string $description = 'Analyze a Laravel class and generate comprehensive test stubs. Supports Controllers, Models, Services, FormRequests, Jobs, Middleware, and Listeners. Returns generated test code for review.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'class_path' => $schema->string()
                ->description('Path to the PHP class file (relative to project root, e.g., "app/Models/User.php")')
                ->required(),

            'test_type' => $schema->string()
                ->enum(['auto', 'unit', 'feature'])
                ->description('Type of test to generate. "auto" detects based on class type.')
                ->default('auto'),

            'include_comments' => $schema->boolean()
                ->description('Include explanatory comments and TODOs in generated tests')
                ->default(true),
        ];
    }

    public function handle(Request $request): Response
    {
        if ($rateLimitResponse = $this->checkRateLimit()) {
            return $rateLimitResponse;
        }

        $validated = $request->validate([
            'class_path' => 'required|string|max:500',
            'test_type' => 'in:auto,unit,feature',
            'include_comments' => 'boolean',
        ]);

        $classPath = $validated['class_path'];
        $testType = $validated['test_type'] ?? 'auto';
        $includeComments = $validated['include_comments'] ?? true;

        try {
            // Analyze the class
            $analyzer = app(ClassAnalyzer::class);
            $analysis = $analyzer->analyze($classPath);

            // Generate tests
            $generator = app(TestGenerator::class);
            $result = $generator->generate($analysis, $includeComments);

            // Override test type if specified
            if ($testType !== 'auto') {
                // The generator already determined the type, but user can override
                // This is handled in the output formatting
            }

            return Response::text($this->formatOutput($analysis, $result, $testType));

        } catch (\InvalidArgumentException $e) {
            return Response::error("Invalid input: {$e->getMessage()}");
        } catch (\Exception $e) {
            return Response::error("Failed to generate test: {$e->getMessage()}");
        }
    }

    private function formatOutput(
        \Kwaku\LaravelTestMcp\DTOs\ClassAnalysis $analysis,
        \Kwaku\LaravelTestMcp\DTOs\GeneratedTest $result,
        string $requestedType
    ): string {
        $output = [];

        // Header
        $output[] = "# Generated Test for {$analysis->shortName}";
        $output[] = '';

        // Class info
        $output[] = '## Class Analysis';
        $output[] = "- **Class:** `{$analysis->className}`";
        $output[] = "- **Type:** {$analysis->type}";
        $output[] = "- **Namespace:** {$analysis->namespace}";

        if ($analysis->parentClass) {
            $output[] = "- **Extends:** `{$analysis->parentClass}`";
        }

        if (!empty($analysis->dependencies)) {
            $output[] = '- **Dependencies:**';
            foreach ($analysis->dependencies as $dep) {
                $output[] = "  - `{$dep}`";
            }
        }

        $output[] = '';

        // Laravel features summary
        if (!empty($analysis->laravelFeatures)) {
            $output[] = '## Detected Features';
            $this->formatLaravelFeatures($output, $analysis);
            $output[] = '';
        }

        // Test info
        $effectiveType = $requestedType === 'auto' ? $result->testType : $requestedType;
        $output[] = '## Generated Test';
        $output[] = "- **Test Type:** {$effectiveType}";
        $output[] = "- **Suggested Path:** `{$result->suggestedTestPath}`";
        $output[] = '';

        // Coverage
        if (!empty($result->coverage)) {
            $output[] = '### Coverage';
            foreach ($result->coverage as $item) {
                $output[] = "- {$item}";
            }
            $output[] = '';
        }

        // Test code
        $output[] = '### Test Code';
        $output[] = '```php';
        $output[] = $result->testContent;
        $output[] = '```';
        $output[] = '';

        // Factory (for models)
        if ($result->hasFactory()) {
            $output[] = '## Generated Factory';
            $output[] = "- **Path:** `{$result->factoryPath}`";
            $output[] = '';
            $output[] = '```php';
            $output[] = $result->factoryContent;
            $output[] = '```';
            $output[] = '';
        }

        // TODOs
        if (!empty($result->todos)) {
            $output[] = '## Next Steps';
            foreach ($result->todos as $todo) {
                $output[] = "- [ ] {$todo}";
            }
            $output[] = '';
        }

        // Usage hint
        $output[] = '---';
        $output[] = '*Copy the test code above and save it to the suggested path. Review and customize the placeholder assertions.*';

        return implode("\n", $output);
    }

    private function formatLaravelFeatures(array &$output, \Kwaku\LaravelTestMcp\DTOs\ClassAnalysis $analysis): void
    {
        $features = $analysis->laravelFeatures;

        if ($analysis->isModel()) {
            if (!empty($features['relationships'])) {
                $output[] = '- **Relationships:**';
                foreach ($features['relationships'] as $rel) {
                    $output[] = "  - `{$rel['name']}()` ({$rel['type']})";
                }
            }

            if (!empty($features['scopes'])) {
                $output[] = '- **Scopes:** ' . implode(', ', array_map(fn($s) => "`{$s}`", $features['scopes']));
            }

            if (!empty($features['accessors'])) {
                $output[] = '- **Accessors:** ' . implode(', ', array_map(fn($a) => "`{$a}`", $features['accessors']));
            }

            if (!empty($features['mutators'])) {
                $output[] = '- **Mutators:** ' . implode(', ', array_map(fn($m) => "`{$m}`", $features['mutators']));
            }

            if (!empty($features['fillable'])) {
                $output[] = '- **Fillable:** ' . implode(', ', array_map(fn($f) => "`{$f}`", $features['fillable']));
            }

            if (!empty($features['casts'])) {
                $casts = array_map(fn($k, $v) => "`{$k}` => `{$v}`", array_keys($features['casts']), $features['casts']);
                $output[] = '- **Casts:** ' . implode(', ', $casts);
            }
        }

        if ($analysis->isController()) {
            if (!empty($features['resourceActions'])) {
                $output[] = '- **Resource Actions:** ' . implode(', ', array_map(fn($a) => "`{$a}`", $features['resourceActions']));
            }

            if (!empty($features['routes'])) {
                $output[] = '- **Routes:**';
                foreach ($features['routes'] as $route) {
                    $methods = implode('|', $route['methods']);
                    $name = $route['name'] ? " ({$route['name']})" : '';
                    $output[] = "  - `[{$methods}]` {$route['uri']}{$name}";
                }
            }
        }

        if ($analysis->isFormRequest()) {
            if (!empty($features['rules'])) {
                $output[] = '- **Validation Rules:** ' . count($features['rules']) . ' fields';
            }

            if ($features['hasAuthorize'] ?? false) {
                $output[] = '- **Has Authorization:** Yes';
            }
        }

        if ($analysis->isJob()) {
            if ($features['isQueueable'] ?? false) {
                $output[] = '- **Queueable:** Yes';
            }

            if (!empty($features['traits'])) {
                $output[] = '- **Traits:** ' . implode(', ', array_map(fn($t) => "`{$t}`", $features['traits']));
            }
        }

        if ($analysis->isMiddleware()) {
            if ($features['hasHandle'] ?? false) {
                $output[] = '- **Has Handle:** Yes';
            }

            if ($features['hasTerminate'] ?? false) {
                $output[] = '- **Has Terminate:** Yes';
            }
        }
    }
}
