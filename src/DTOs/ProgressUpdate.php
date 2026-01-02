<?php

namespace Kwaku\LaravelTestMcp\DTOs;

readonly class ProgressUpdate
{
    public function __construct(
        public string $stage,
        public int $completed,
        public int $total,
        public float $percentComplete,
        public ?string $currentItem = null,
        public ?string $operationId = null,
    ) {}

    public function format(): string
    {
        $percent = number_format($this->percentComplete, 1);
        $progress = "[{$this->completed}/{$this->total}] {$percent}%";

        if ($this->currentItem) {
            return "⏳ {$this->stage}: {$progress} - {$this->currentItem}";
        }

        return "⏳ {$this->stage}: {$progress}";
    }
}
