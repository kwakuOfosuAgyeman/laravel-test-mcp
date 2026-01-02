# Laravel Test Runner MCP

Run Pest and PHPUnit tests directly from AI assistants like Claude, Cursor, and ChatGPT.

## Features

- **Progress Tracking**: Real-time progress updates during test execution
- **Cancellation Support**: Cancel long-running operations via operation ID
- **Dry-Run Mode**: Preview what tests would run without executing
- **Confirmation Threshold**: Safety check for large test suites
- **MCP Prompts**: Built-in prompts for TDD workflow, debugging, and coverage analysis

## Installation

```bash
composer require kwaku/laravel-test-mcp
```

## Configuration

Add to your MCP client config:

### Claude Desktop

```json
{
    "mcpServers": {
        "laravel-tests": {
            "command": "php",
            "args": ["/path/to/your/project/artisan", "mcp:start", "test-runner", "--stdio"]
        }
    }
}
```

### Cursor

Same as above, in Cursor's MCP settings.

## Available Tools

### run_tests

Run tests with optional filtering by path, name, or group.

**Parameters:**
- `path` - File or directory path to test
- `filter` - Filter tests by name (regex supported)
- `group` - Run only tests in specific group(s)
- `exclude_group` - Exclude tests in specific group(s)
- `stop_on_failure` - Stop on first failure
- `parallel` - Run tests in parallel (Pest only)
- `dry_run` - Preview tests without running
- `force` - Skip confirmation for large suites

**Example prompts:**
- "Run the user tests" → `run_tests path: "tests/Unit/UserTest.php"`
- "Run all feature tests" → `run_tests path: "tests/Feature"`
- "Preview what tests would run" → `run_tests dry_run: true`

### list_tests

List all available tests without running them.

**Parameters:**
- `path` - Directory to scan
- `format` - Output format: tree, flat, or json

### get_coverage

Run tests with code coverage analysis.

**Parameters:**
- `path` - Path to test or directory
- `filter_file` - Only show coverage for specific file
- `min_coverage` - Fail if coverage is below this percentage
- `format` - Output format: summary, detailed, or uncovered
- `dry_run` - Preview without running

**Example prompts:**
- "Check my code coverage" → `get_coverage`
- "Get coverage for User model" → `get_coverage filter_file: "app/Models/User.php"`
- "Fail if coverage is below 80%" → `get_coverage min_coverage: 80`

### watch_tests

Watch for file changes and auto-run related tests.

**Parameters:**
- `path` - Directory to watch (default: app)
- `test_path` - Test directory (default: tests)
- `duration` - How long to watch in seconds (max: 300)

**Example prompts:**
- "Watch my code for 5 minutes" → `watch_tests duration: 300`
- "Start TDD mode" → `watch_tests`

### cancel_operation

Cancel a running operation by its ID.

**Parameters:**
- `operation_id` - The operation ID (e.g., op_abc123)

**Example prompts:**
- "Cancel the current operation" → `cancel_operation operation_id: "op_xxx"`

### mutation_test

Run mutation testing with Infection to find weak tests.

**Parameters:**
- `path` - Path to test
- `min_msi` - Minimum mutation score indicator

## Available Resources

### test://results/latest
Get the latest test run results.

### test://coverage/summary
Get code coverage statistics.

### test://config
Get test configuration details.

### test://files/{path}
Read the contents of a specific test file.

### test://history
Get historical test results and trends.

### test://coverage/uncovered/{file}
Show uncovered code snippets for a file.

## Available Prompts

### tdd_workflow
Get a structured guide for Test-Driven Development.

**Arguments:**
- `feature` (required) - The feature to implement
- `type` - Test type: unit, feature, or integration

### debug_failing_test
Get debugging strategies for a failing test.

**Arguments:**
- `test_name` (required) - Name of the failing test
- `error_message` - The error message

### analyze_coverage
Get recommendations for improving test coverage.

**Arguments:**
- `target_coverage` - Target percentage (default: 80)
- `focus_area` - Area to focus on (e.g., Models, Controllers)

## Environment Variables

```env
# Test framework: 'pest', 'phpunit', or leave empty for auto-detection
TEST_MCP_FRAMEWORK=

# Test execution timeout in seconds (default: 300)
TEST_MCP_TIMEOUT=300

# Coverage driver: 'xdebug', 'pcov', or leave empty for auto-detection
TEST_MCP_COVERAGE_DRIVER=

# Confirmation threshold: warn when running more than N tests (default: 50)
TEST_MCP_CONFIRMATION_THRESHOLD=50

# Rate limiting
TEST_MCP_RATE_LIMIT_ENABLED=true
TEST_MCP_RATE_LIMIT_MAX_ATTEMPTS=60
TEST_MCP_RATE_LIMIT_DECAY_SECONDS=60
```

## Progress Tracking

Tools that support progress tracking will output:
1. Operation ID for cancellation
2. Stage updates during execution
3. Percentage completion

Example output:
```
🚀 Operation started (ID: op_abc123xyz)
🔍 Discovering tests...
⏳ Running tests: [5/10] 50.0%
✅ PASSED | 10/10 tests | 2.5s
```

## Cancellation

To cancel a running operation:
1. Note the operation ID from the tool output
2. Use `cancel_operation` with that ID
3. The operation will stop at the next checkpoint

## Troubleshooting

### Tests not running
- Ensure Pest or PHPUnit is installed: `composer require pestphp/pest --dev`
- Check the test path exists
- Verify permissions on vendor/bin/pest or vendor/bin/phpunit

### Coverage not working
- Install Xdebug: `pecl install xdebug`
- Or install PCOV: `pecl install pcov`
- Enable in php.ini

### Rate limit exceeded
- Wait for the decay period (default: 60 seconds)
- Increase `TEST_MCP_RATE_LIMIT_MAX_ATTEMPTS` in .env

### Large test suite warnings
- Use `force: true` to run anyway
- Use `dry_run: true` to preview first
- Adjust `TEST_MCP_CONFIRMATION_THRESHOLD` in .env

## License

MIT
