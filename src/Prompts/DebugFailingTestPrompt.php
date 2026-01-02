<?php

namespace Kwaku\LaravelTestMcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class DebugFailingTestPrompt extends Prompt
{
    protected string $name = 'debug_failing_test';

    protected string $title = 'Debug Failing Test';

    protected string $description = 'Get a structured guide for debugging a failing test with common solutions';

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'test_name',
                description: 'Name of the failing test (e.g., UserTest::test_can_login)',
                required: true,
            ),
            new Argument(
                name: 'error_message',
                description: 'The error message or assertion failure',
                required: false,
            ),
        ];
    }

    public function handle(Request $request): Response
    {
        $testName = $request->input('test_name', 'unknown test');
        $errorMessage = $request->input('error_message', '');

        $guide = $this->generateDebugGuide($testName, $errorMessage);

        return Response::text($guide);
    }

    private function generateDebugGuide(string $testName, string $errorMessage): string
    {
        $errorSection = $errorMessage
            ? "\n**Error Message:**\n```\n{$errorMessage}\n```\n"
            : '';

        return <<<MARKDOWN
# Debugging: {$testName}
{$errorSection}
---

## Step 1: Reproduce the Failure

Run the specific test to see the full error:
```
Use run_tests with filter: "{$testName}"
```

---

## Step 2: Analyze the Error Type

### Common Error Categories:

**Assertion Failures**
- `Expected X, got Y` - Value mismatch
- `Failed asserting that...` - Condition not met
- `null does not match expected type` - Missing return value

**Exception Errors**
- `Class not found` - Missing import or autoload
- `Method does not exist` - Wrong method name or missing method
- `Too few arguments` - Method signature changed

**Database Errors**
- `SQLSTATE` - Database constraint or query issue
- `Table not found` - Missing migration
- `Duplicate entry` - Unique constraint violation

---

## Step 3: Common Solutions

### Solution A: Check Test Setup
```php
beforeEach(function () {
    // Is the database properly seeded?
    // Are required services mocked?
    // Is the authentication state correct?
});
```

### Solution B: Verify Expected Values
```php
// Add debugging to see actual values
dump(\$actualValue);
dd(\$result); // Die and dump

// Or use Pest's debugging
ray(\$variable); // If using Ray
```

### Solution C: Check Recent Changes
```bash
git diff HEAD~5 -- app/ tests/
```

### Solution D: Isolate the Issue
```php
test('isolated behavior', function () {
    // Test only the specific failing behavior
    // Remove unrelated assertions
});
```

---

## Step 4: Debug Strategies

### For Assertion Failures:
1. Print the actual value before assertion
2. Check if the data setup is correct
3. Verify the method returns expected type

### For Database Errors:
1. Run migrations: `php artisan migrate:fresh --env=testing`
2. Check factory definitions
3. Verify foreign key relationships

### For Mock/Dependency Issues:
1. Ensure mocks return expected values
2. Check if real services are being called
3. Verify dependency injection bindings

---

## Step 5: Run with Coverage

See which lines are executing:
```
Use get_coverage with filter_file: "path/to/related/file.php"
```

---

## Quick Reference

| Symptom | Likely Cause | Fix |
|---------|-------------|-----|
| Null value | Missing return | Add return statement |
| Wrong type | Type coercion | Cast or validate input |
| Not found | Missing setup | Add to beforeEach/setUp |
| Timeout | Infinite loop | Check conditions/loops |

---

## Still Stuck?

1. Read the test file: `Use test://files/{path}` resource
2. Check test history: `Use test://history` resource
3. Run all related tests: `Use run_tests with path to directory`
MARKDOWN;
    }
}
