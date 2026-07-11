<?php

declare(strict_types=1);

namespace Lmad\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Lmad\Mcp\Services\EndpointAnalyzerService;

/**
 * MCP Tool for comprehensive endpoint analysis.
 *
 * Aggregates route, controller, FormRequest, and response information
 * in a single call.
 */
final class AnalyzeEndpoint extends Tool
{
    /** Tool name */
    protected string $name = 'analyze_endpoint';

    /** Tool description */
    protected string $description = 'Performs comprehensive endpoint analysis combining route info, controller details, request validation rules, and response schema in one call.';

    /**
     * JSON Schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'uri' => $schema->string()->description('The route URI pattern (e.g., \'api/users/{id}\')')->required(),
            'method' => $schema->string()->description('The HTTP method')->enum(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'])->required(),
        ];
    }

    /** Meta information */
    protected ?array $meta = [
        'category' => 'analysis',
        'author' => '0xmergen',
    ];

    public function __construct(
        private readonly EndpointAnalyzerService $analyzer
    ) {}

    /**
     * Handles the MCP request.
     *
     * @param  Request  $request  MCP request
     * @return Response|ResponseFactory Analysis results or error
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $uri = (string) $request->get('uri');
        $method = strtoupper((string) $request->get('method'));

        if (empty($uri)) {
            return Response::error('URI is required.');
        }

        if (empty($method)) {
            return Response::error('Method is required.');
        }

        return $this->analyzer->analyze($uri, $method);
    }
}
