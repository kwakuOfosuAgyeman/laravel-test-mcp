<?php

namespace Kwaku\LaravelTestMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Kwaku\LaravelTestMcp\Services\CancellationToken;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CancelOperationTool extends Tool
{
    protected string $name = 'cancel_operation';

    protected string $description = 'Cancel a running operation by its operation ID. Use this to stop long-running tests or watch sessions.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'operation_id' => $schema->string()
                ->description('The operation ID returned when the operation started (e.g., op_abc123xyz)')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'operation_id' => 'required|string|max:50',
        ]);

        $operationId = $validated['operation_id'];

        // Validate operation ID format
        if (!preg_match('/^op_[a-zA-Z0-9]+$/', $operationId)) {
            return Response::error("Invalid operation ID format. Expected format: op_xxxxx");
        }

        $token = CancellationToken::forOperation($operationId);

        if ($token->isCancelled()) {
            return Response::text("⚠️ Operation {$operationId} was already cancelled");
        }

        $token->cancel();

        return Response::text("✅ Cancellation requested for operation {$operationId}");
    }
}
