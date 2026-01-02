<?php

namespace Kwaku\LaravelTestMcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class AnalyzeCoveragePrompt extends Prompt
{
    protected string $name = 'analyze_coverage';

    protected string $title = 'Analyze Coverage';

    protected string $description = 'Get recommendations for improving test coverage based on current coverage report';

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'target_coverage',
                description: 'Target coverage percentage (default: 80)',
                required: false,
            ),
            new Argument(
                name: 'focus_area',
                description: 'Specific area to focus on (e.g., Models, Controllers, Services)',
                required: false,
            ),
        ];
    }

    public function handle(Request $request): Response
    {
        $targetCoverage = (int) $request->input('target_coverage', 80);
        $focusArea = $request->input('focus_area', '');

        $guide = $this->generateCoverageGuide($targetCoverage, $focusArea);

        return Response::text($guide);
    }

    private function generateCoverageGuide(int $targetCoverage, string $focusArea): string
    {
        $focusSection = $focusArea
            ? "\n**Focus Area:** {$focusArea}\n"
            : '';

        return <<<MARKDOWN
# Code Coverage Analysis Guide

**Target Coverage:** {$targetCoverage}%{$focusSection}

---

## Step 1: Get Current Coverage

Run coverage analysis:
```
Use get_coverage tool with format: "summary"
```

For detailed uncovered lines:
```
Use get_coverage tool with format: "uncovered"
```

---

## Step 2: Prioritize What to Test

### High Priority (Test First)
1. **Business Logic** - Core domain rules
2. **Public APIs** - Controllers, API endpoints
3. **Data Validation** - Form requests, validators
4. **Security** - Authentication, authorization

### Medium Priority
1. **Service Classes** - Application services
2. **Repository Methods** - Data access logic
3. **Event Handlers** - Listeners, jobs

### Lower Priority
1. **Simple Getters/Setters** - Trivial accessors
2. **Framework Boilerplate** - Generated code
3. **Configuration** - Static config files

---

## Step 3: Common Coverage Gaps

### Models (app/Models/)
```php
// Test relationships
test('user has many posts', function () {
    \$user = User::factory()->hasPosts(3)->create();
    expect(\$user->posts)->toHaveCount(3);
});

// Test scopes
test('active scope filters correctly', function () {
    User::factory()->count(3)->create(['active' => true]);
    User::factory()->count(2)->create(['active' => false]);
    expect(User::active()->count())->toBe(3);
});

// Test accessors/mutators
test('full name accessor combines names', function () {
    \$user = new User(['first_name' => 'John', 'last_name' => 'Doe']);
    expect(\$user->full_name)->toBe('John Doe');
});
```

### Controllers (app/Http/Controllers/)
```php
// Test HTTP responses
test('index returns paginated users', function () {
    User::factory()->count(15)->create();

    \$response = \$this->get('/users');

    \$response->assertOk()
        ->assertViewHas('users');
});

// Test validation
test('store validates required fields', function () {
    \$response = \$this->post('/users', []);

    \$response->assertSessionHasErrors(['name', 'email']);
});
```

### Services (app/Services/)
```php
// Test main functionality
test('payment service processes payment', function () {
    \$service = app(PaymentService::class);

    \$result = \$service->process(100.00, 'usd');

    expect(\$result->success)->toBeTrue();
});

// Test edge cases
test('payment service handles failure', function () {
    // Mock the gateway to fail
    \$result = \$service->process(-1, 'usd');

    expect(\$result->success)->toBeFalse();
});
```

---

## Step 4: Coverage Improvement Strategies

### Strategy A: Cover Branches
```php
// Cover both if and else branches
if (\$condition) {
    // Write test for this path
} else {
    // Write test for this path too
}
```

### Strategy B: Cover Edge Cases
- Empty arrays/collections
- Null values
- Boundary values (0, -1, max)
- Invalid input

### Strategy C: Cover Error Paths
```php
test('throws exception for invalid input', function () {
    expect(fn() => \$service->process(null))
        ->toThrow(InvalidArgumentException::class);
});
```

---

## Step 5: Track Progress

Check coverage for specific file:
```
Use get_coverage with filter_file: "app/Services/PaymentService.php"
```

Set minimum threshold:
```
Use get_coverage with min_coverage: {$targetCoverage}
```

---

## Coverage Goals by Layer

| Layer | Recommended Coverage |
|-------|---------------------|
| Models | 70-80% |
| Services | 85-95% |
| Controllers | 70-80% |
| Middleware | 80-90% |
| Jobs/Events | 75-85% |

---

## Quick Wins

1. **Test factories exist** - Ensure all models have factories
2. **Test happy paths first** - Cover the main success scenarios
3. **Use data providers** - Test multiple inputs efficiently
4. **Mock external services** - Don't let API calls slow you down

---

## Resources

- View uncovered code: `Use test://coverage/uncovered/{file}`
- Latest coverage summary: `Use test://coverage/summary`
- Run focused tests: `Use run_tests with path to specific test`
MARKDOWN;
    }
}
