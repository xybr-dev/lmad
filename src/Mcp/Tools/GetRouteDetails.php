<?php

declare(strict_types=1);

namespace Lmad\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Lmad\Mcp\Schema\ControllerInspector;
use Lmad\Mcp\Schema\RouteParser;

/**
 * MCP Tool for retrieving route details.
 *
 * Returns route information, controller details, file path and line
 * number information.
 */
final class GetRouteDetails extends Tool
{
    /** Tool name */
    protected string $name = 'get_route_details';

    /** Tool description */
    protected string $description = 'Gets detailed information about a specific route including controller, file path, line numbers, middleware, and request validation class.';

    /**
     * JSON Schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'uri' => [
                    'type' => 'string',
                    'description' => 'The route URI pattern (e.g., \'api/users/{id}\')',
                ],
                'method' => [
                    'type' => 'string',
                    'description' => 'The HTTP method',
                    'enum' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                ],
            ],
            'required' => ['uri', 'method'],
        ];
    }

    /** Meta information */
    protected ?array $meta = [
        'category' => 'discovery',
        'author' => '0xmergen',
    ];

    public function __construct(
        private readonly RouteParser $routeParser,
        private readonly ControllerInspector $controllerInspector,
    ) {}

    /**
     * Handles the MCP request.
     *
     * @param  Request  $request  MCP request
     * @return Response|ResponseFactory Route details or error
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

        $routeInfo = $this->routeParser->parseByUriAndMethod($uri, $method);

        if ($routeInfo === null) {
            return Response::error("No route found for URI '{$uri}' with method '{$method}'.");
        }

        $controllerClass = $routeInfo['controller']['class'] ?? null;
        $controllerMethod = $routeInfo['controller']['method'] ?? null;

        $details = [
            'route' => $routeInfo,
        ];

        if ($controllerClass && $controllerMethod) {
            $details['controller'] = $this->controllerInspector->inspect($controllerClass, $controllerMethod);

            $requestClass = $this->controllerInspector->getRequestClass($controllerClass, $controllerMethod);
            if ($requestClass) {
                $details['request_class'] = $requestClass;
            }

            $resourceClass = $this->controllerInspector->getResourceClass($controllerClass, $controllerMethod);
            if ($resourceClass) {
                $details['resource_class'] = $resourceClass;
            }
        }

        return Response::structured($details);
    }
}
