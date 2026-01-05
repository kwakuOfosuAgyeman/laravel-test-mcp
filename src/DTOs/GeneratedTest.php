<?php

namespace Kwaku\LaravelTestMcp\DTOs;

readonly class GeneratedTest
{
    /**
     * @param array<string> $coverage List of methods/features covered by generated tests
     * @param array<string> $todos Manual steps or notes for the developer
     */
    public function __construct(
        public string $testContent,
        public string $suggestedTestPath,
        public string $testType,
        public ?string $factoryContent = null,
        public ?string $factoryPath = null,
        public array $coverage = [],
        public array $todos = [],
    ) {}

    public function hasFactory(): bool
    {
        return $this->factoryContent !== null;
    }

    public function format(): string
    {
        $output = [];

        $output[] = "## Generated Test";
        $output[] = "";
        $output[] = "**Type:** {$this->testType}";
        $output[] = "**Suggested path:** `{$this->suggestedTestPath}`";
        $output[] = "";
        $output[] = "### Test Code";
        $output[] = "```php";
        $output[] = $this->testContent;
        $output[] = "```";

        if ($this->hasFactory()) {
            $output[] = "";
            $output[] = "### Factory Code";
            $output[] = "**Path:** `{$this->factoryPath}`";
            $output[] = "```php";
            $output[] = $this->factoryContent;
            $output[] = "```";
        }

        if (!empty($this->coverage)) {
            $output[] = "";
            $output[] = "### Coverage";
            foreach ($this->coverage as $item) {
                $output[] = "- {$item}";
            }
        }

        if (!empty($this->todos)) {
            $output[] = "";
            $output[] = "### TODOs";
            foreach ($this->todos as $todo) {
                $output[] = "- [ ] {$todo}";
            }
        }

        return implode("\n", $output);
    }
}
