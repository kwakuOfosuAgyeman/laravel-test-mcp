<?php

namespace Kwaku\LaravelTestMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Process;
use Kwaku\LaravelTestMcp\Concerns\HasRateLimiting;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class MutationTestTool extends Tool
{
    use HasRateLimiting;

    protected string $name = 'mutation_test';
    
    protected string $description = 'Run mutation testing with Infection to find weak tests. Shows which code changes would NOT be caught by your tests.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('Source file or directory to mutate')
                ->nullable(),
            
            'min_msi' => $schema->integer()
                ->description('Minimum Mutation Score Indicator (percentage)')
                ->default(70)
                ->minimum(0)
                ->maximum(100),
            
            'only_covered' => $schema->boolean()
                ->description('Only mutate covered code')
                ->default(true),
        ];
    }

    public function handle(Request $request): Response
    {
        if ($rateLimitResponse = $this->checkRateLimit()) {
            return $rateLimitResponse;
        }

        // Check if Infection is installed
        if (!file_exists(base_path('vendor/bin/infection'))) {
            return Response::error(
                'Infection is not installed. Run: composer require --dev infection/infection'
            );
        }
        
        $command = ['./vendor/bin/infection'];
        
        if ($request->input('only_covered', true)) {
            $command[] = '--only-covered';
        }
        
        $command[] = '--min-msi=' . $request->input('min_msi', 70);
        $command[] = '--threads=4';
        $command[] = '--logger-github=false';
        $command[] = '--show-mutations';
        
        if ($path = $request->input('path')) {
            $command[] = "--filter={$path}";
        }
        
        $process = Process::path(base_path())
            ->timeout(900) // Mutation testing is very slow
            ->run($command);
        
        return Response::text($this->formatMutationResults($process->output()));
    }
    
    private function formatMutationResults(string $output): string
    {
        // Parse Infection output and format nicely
        $formatted = ["# Mutation Testing Results\n"];

        // Extract MSI score
        if (preg_match('/Mutation Score Indicator \(MSI\): (\d+)%/', $output, $matches)) {
            $msi = $matches[1];
            $emoji = $msi >= 80 ? '✅' : ($msi >= 60 ? '⚠️' : '❌');
            $formatted[] = "{$emoji} **MSI: {$msi}%**\n";
        }

        // Extract escaped mutants (weak tests)
        if (preg_match_all('/Escaped mutant:.*?(?=Escaped mutant:|$)/s', $output, $matches)) {
            $formatted[] = "## Escaped Mutants (Weak Tests)\n";
            foreach ($matches[0] as $mutant) {
                $formatted[] = "```";
                $formatted[] = trim($mutant);
                $formatted[] = "```\n";
            }
        }

        return implode("\n", $formatted);
    }
}