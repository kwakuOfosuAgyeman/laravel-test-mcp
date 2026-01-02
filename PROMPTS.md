# Example AI Prompts for Laravel Test MCP

This document provides example prompts you can use with AI assistants to interact with the Laravel Test MCP server.

## Running Tests

### Basic Test Execution
```
Run my tests
```
→ Uses `run_tests` to execute all tests

```
Run the user tests
```
→ Uses `run_tests` with path filtering

```
Run tests/Feature/AuthTest.php
```
→ Uses `run_tests` with specific file path

### Filtered Test Execution
```
Run only tests that contain "login"
```
→ Uses `run_tests` with `filter: "login"`

```
Run the unit tests for the User model
```
→ Uses `run_tests` with `path: "tests/Unit/Models/UserTest.php"`

```
Run all feature tests except the slow ones
```
→ Uses `run_tests` with `path: "tests/Feature"` and `exclude_group: "slow"`

### Preview and Safety
```
What tests would run if I ran the full suite?
```
→ Uses `run_tests` with `dry_run: true`

```
Run all tests, I know there are a lot
```
→ Uses `run_tests` with `force: true`

```
Stop the running tests
```
→ Uses `cancel_operation` with the operation ID

## Code Coverage

### Getting Coverage Reports
```
Check my code coverage
```
→ Uses `get_coverage`

```
What's the coverage for the User model?
```
→ Uses `get_coverage` with `filter_file: "app/Models/User.php"`

```
Show me uncovered lines
```
→ Uses `get_coverage` with `format: "uncovered"`

### Coverage Thresholds
```
Make sure coverage is at least 80%
```
→ Uses `get_coverage` with `min_coverage: 80`

```
What files have less than 70% coverage?
```
→ Uses `get_coverage` with `format: "summary"` then analyzes output

## Test Discovery

### Listing Tests
```
What tests do I have?
```
→ Uses `list_tests`

```
Show me all tests in the Feature directory
```
→ Uses `list_tests` with `path: "tests/Feature"`

```
List tests as JSON
```
→ Uses `list_tests` with `format: "json"`

## Watch Mode (TDD)

### Starting Watch Mode
```
Watch my code and run tests automatically
```
→ Uses `watch_tests`

```
Start TDD mode for 5 minutes
```
→ Uses `watch_tests` with `duration: 300`

```
Watch the app/Services directory
```
→ Uses `watch_tests` with `path: "app/Services"`

### Stopping Watch Mode
```
Stop watching
```
→ Uses `cancel_operation` with the watch operation ID

## Using Prompts

### TDD Workflow
```
Help me implement user registration using TDD
```
→ Uses `tdd_workflow` prompt with `feature: "user registration"`

```
Guide me through TDD for a payment feature
```
→ Uses `tdd_workflow` prompt with `feature: "payment processing"` and `type: "feature"`

### Debugging Failing Tests
```
Help me debug UserTest::test_can_login, it's failing with "expected true, got null"
```
→ Uses `debug_failing_test` prompt with test name and error message

```
Why is my authentication test failing?
```
→ Uses `debug_failing_test` prompt

### Coverage Analysis
```
How can I improve my test coverage?
```
→ Uses `analyze_coverage` prompt

```
Help me get to 90% coverage on my Services
```
→ Uses `analyze_coverage` prompt with `target_coverage: 90` and `focus_area: "Services"`

## Reading Resources

### Test Results
```
Show me the last test results
```
→ Reads `test://results/latest` resource

```
What was my last test run?
```
→ Reads `test://results/latest` resource

### Configuration
```
What test framework am I using?
```
→ Reads `test://config` resource

```
Show my test configuration
```
→ Reads `test://config` resource

### Test Files
```
Show me the UserTest file
```
→ Reads `test://files/tests/Unit/UserTest.php` resource

### History and Trends
```
Have my tests been flaky lately?
```
→ Reads `test://history` resource

```
Show me my test history
```
→ Reads `test://history` resource

### Uncovered Code
```
Show me uncovered code in User.php
```
→ Reads `test://coverage/uncovered/app/Models/User.php` resource

## Combined Workflows

### Complete TDD Session
```
1. "Start TDD mode for implementing a new Comment feature"
   → Uses tdd_workflow prompt

2. "Watch my code"
   → Uses watch_tests

3. [Write failing test]

4. [See it fail in watch output]

5. [Write code to make it pass]

6. [See it pass in watch output]

7. "Stop watching"
   → Uses cancel_operation

8. "Check my coverage"
   → Uses get_coverage
```

### Debugging Session
```
1. "Run the failing test"
   → Uses run_tests

2. "Help me debug this failure"
   → Uses debug_failing_test prompt

3. "Show me the test file"
   → Reads test://files resource

4. "Check coverage for the related class"
   → Uses get_coverage with filter_file

5. "Run the test again"
   → Uses run_tests
```

### Coverage Improvement Session
```
1. "How can I improve coverage?"
   → Uses analyze_coverage prompt

2. "Show me what's not covered"
   → Uses get_coverage with format: "uncovered"

3. "Show me uncovered code in UserService.php"
   → Reads test://coverage/uncovered resource

4. [Write tests for uncovered code]

5. "Run coverage again"
   → Uses get_coverage

6. "Did I hit 80%?"
   → Uses get_coverage with min_coverage: 80
```

## Tips

1. **Be specific about paths** - "Run the User tests" is less precise than "Run tests/Unit/UserTest.php"

2. **Use dry_run first** - Before running large test suites, preview what would run

3. **Watch mode for TDD** - Let the watch tool run while you code for instant feedback

4. **Check coverage regularly** - Use `get_coverage` after implementing new features

5. **Use prompts for guidance** - The built-in prompts provide structured workflows for common tasks
