<?php

use Kwaku\LaravelTestMcp\DTOs\GeneratedTest;

test('GeneratedTest can be instantiated with basic properties', function () {
    $result = new GeneratedTest(
        testContent: '<?php test("example", function () { });',
        suggestedTestPath: 'tests/Unit/ExampleTest.php',
        testType: 'unit',
    );

    expect($result->testContent)->toContain('test("example"');
    expect($result->suggestedTestPath)->toBe('tests/Unit/ExampleTest.php');
    expect($result->testType)->toBe('unit');
    expect($result->factoryContent)->toBeNull();
    expect($result->factoryPath)->toBeNull();
});

test('GeneratedTest can include factory content', function () {
    $result = new GeneratedTest(
        testContent: '<?php test("example", function () { });',
        suggestedTestPath: 'tests/Unit/UserTest.php',
        testType: 'unit',
        factoryContent: '<?php class UserFactory extends Factory { }',
        factoryPath: 'database/factories/UserFactory.php',
    );

    expect($result->hasFactory())->toBeTrue();
    expect($result->factoryContent)->toContain('UserFactory');
    expect($result->factoryPath)->toBe('database/factories/UserFactory.php');
});

test('GeneratedTest hasFactory returns false when no factory', function () {
    $result = new GeneratedTest(
        testContent: '<?php test("example", function () { });',
        suggestedTestPath: 'tests/Unit/ExampleTest.php',
        testType: 'unit',
    );

    expect($result->hasFactory())->toBeFalse();
});

test('GeneratedTest can include coverage list', function () {
    $result = new GeneratedTest(
        testContent: '<?php',
        suggestedTestPath: 'tests/Unit/ExampleTest.php',
        testType: 'unit',
        coverage: ['index()', 'store()', 'update()', 'destroy()'],
    );

    expect($result->coverage)->toHaveCount(4);
    expect($result->coverage)->toContain('index()');
});

test('GeneratedTest can include todos', function () {
    $result = new GeneratedTest(
        testContent: '<?php',
        suggestedTestPath: 'tests/Unit/ExampleTest.php',
        testType: 'unit',
        todos: ['Add edge case tests', 'Review mocked dependencies'],
    );

    expect($result->todos)->toHaveCount(2);
    expect($result->todos[0])->toBe('Add edge case tests');
});

test('GeneratedTest format outputs markdown', function () {
    $result = new GeneratedTest(
        testContent: '<?php test("example", function () { });',
        suggestedTestPath: 'tests/Unit/ExampleTest.php',
        testType: 'unit',
    );

    $formatted = $result->format();

    expect($formatted)->toContain('## Generated Test');
    expect($formatted)->toContain('**Type:** unit');
    expect($formatted)->toContain('tests/Unit/ExampleTest.php');
    expect($formatted)->toContain('```php');
});

test('GeneratedTest format includes factory when present', function () {
    $result = new GeneratedTest(
        testContent: '<?php test("example", function () { });',
        suggestedTestPath: 'tests/Unit/UserTest.php',
        testType: 'unit',
        factoryContent: '<?php class UserFactory { }',
        factoryPath: 'database/factories/UserFactory.php',
    );

    $formatted = $result->format();

    expect($formatted)->toContain('### Factory Code');
    expect($formatted)->toContain('database/factories/UserFactory.php');
    expect($formatted)->toContain('UserFactory');
});

test('GeneratedTest format includes coverage when present', function () {
    $result = new GeneratedTest(
        testContent: '<?php',
        suggestedTestPath: 'tests/Unit/ExampleTest.php',
        testType: 'unit',
        coverage: ['method1()', 'method2()'],
    );

    $formatted = $result->format();

    expect($formatted)->toContain('### Coverage');
    expect($formatted)->toContain('- method1()');
    expect($formatted)->toContain('- method2()');
});

test('GeneratedTest format includes todos when present', function () {
    $result = new GeneratedTest(
        testContent: '<?php',
        suggestedTestPath: 'tests/Unit/ExampleTest.php',
        testType: 'unit',
        todos: ['Review assertions', 'Add more tests'],
    );

    $formatted = $result->format();

    expect($formatted)->toContain('### TODOs');
    expect($formatted)->toContain('- [ ] Review assertions');
    expect($formatted)->toContain('- [ ] Add more tests');
});
