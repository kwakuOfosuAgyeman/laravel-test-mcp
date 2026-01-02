<?php

namespace Kwaku\LaravelTestMcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class TddWorkflowPrompt extends Prompt
{
    protected string $name = 'tdd_workflow';

    protected string $title = 'TDD Workflow';

    protected string $description = 'Guide through Test-Driven Development red-green-refactor cycle for implementing a feature';

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'feature',
                description: 'The feature or functionality to implement using TDD',
                required: true,
            ),
            new Argument(
                name: 'type',
                description: 'Type of test: unit, feature, or integration',
                required: false,
            ),
        ];
    }

    public function handle(Request $request): Response
    {
        $feature = $request->input('feature', 'new feature');
        $type = $request->input('type', 'unit');

        $workflow = $this->generateWorkflow($feature, $type);

        return Response::text($workflow);
    }

    private function generateWorkflow(string $feature, string $type): string
    {
        $testType = ucfirst($type);

        return <<<MARKDOWN
# TDD Workflow: {$feature}

## Overview
Follow the Red-Green-Refactor cycle to implement **{$feature}** using Test-Driven Development.

---

## Phase 1: RED (Write Failing Test)

### Step 1: Create the test file
```bash
# Create a {$testType} test
php artisan make:test {$this->featureToTestName($feature)}Test --{$type}
```

### Step 2: Write the test
Write a test that describes the expected behavior:
```php
test('{$this->featureToTestDescription($feature)}', function () {
    // Arrange: Set up test data

    // Act: Perform the action

    // Assert: Verify the expected outcome
    expect(\$result)->toBe(\$expected);
});
```

### Step 3: Run the test (should FAIL)
```
Use the run_tests tool with path: "tests/{$testType}/{$this->featureToTestName($feature)}Test.php"
```

---

## Phase 2: GREEN (Make It Pass)

### Step 4: Write minimal code
Write just enough code to make the test pass. Don't over-engineer!

### Step 5: Run the test (should PASS)
```
Use the run_tests tool to verify the test passes
```

---

## Phase 3: REFACTOR (Improve Code)

### Step 6: Clean up the code
- Remove duplication
- Improve naming
- Simplify logic

### Step 7: Run all tests
```
Use the run_tests tool to ensure nothing broke
```

---

## TDD Tips for {$feature}

1. **Start small** - Write one simple test case first
2. **Be specific** - Test one behavior per test
3. **Use descriptive names** - Make test names read like documentation
4. **Don't skip steps** - Always see the test fail first
5. **Refactor often** - Clean code is easier to maintain

---

## Using Watch Mode

For continuous feedback during TDD:
```
Use the watch_tests tool with duration: 300
```

This will automatically run related tests when you save files.

---

## Next Steps

1. Identify the simplest test case for **{$feature}**
2. Write that test
3. Make it pass
4. Repeat!
MARKDOWN;
    }

    private function featureToTestName(string $feature): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $feature)));
    }

    private function featureToTestDescription(string $feature): string
    {
        return strtolower(str_replace(['-', '_'], ' ', $feature));
    }
}
