<?php

declare(strict_types=1);

namespace Lmad\Mcp\Services;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Lmad\Mcp\Schema\ControllerInspector;
use Lmad\Mcp\Schema\RequestInspector;
use Lmad\Mcp\Schema\ResponseInspector;
use Lmad\Mcp\Schema\RouteParser;

/**
 * Service for comprehensive API endpoint analysis.
 *
 * Aggregates route, controller, FormRequest, and response information
 * to provide complete endpoint analysis.
 */
final class EndpointAnalyzerService
{
    public function __construct(
        private readonly RouteParser $routeParser,
        private readonly ControllerInspector $controllerInspector,
        private readonly RequestInspector $requestInspector,
        private readonly ResponseInspector $responseInspector,
        private readonly ExampleGeneratorService $exampleGenerator,
    ) {}

    /**
     * Analyzes an endpoint comprehensively.
     *
     * Returns route information, controller details, FormRequest validation
     * rules, response schema, and example request/response.
     *
     * @param  string  $uri  Endpoint URI (e.g., "api/users")
     * @param  string  $method  HTTP method (e.g., "GET", "POST")
     * @return Response|ResponseFactory Analysis results or error
     */
    public function analyze(string $uri, string $method): Response|ResponseFactory
    {
        $routeInfo = $this->routeParser->parseByUriAndMethod($uri, $method);

        if ($routeInfo === null) {
            return Response::error("No route found for URI '{$uri}' with method '{$method}'.");
        }

        $controllerClass = $routeInfo['controller']['class'] ?? null;
        $controllerMethod = $routeInfo['controller']['method'] ?? null;

        $analysis = [
            'endpoint' => [
                'uri' => $uri,
                'method' => $method,
                'name' => $routeInfo['name'],
            ],
            'route' => $routeInfo,
        ];

        if ($controllerClass && $controllerMethod) {
            $analysis['controller'] = $this->controllerInspector->inspect($controllerClass, $controllerMethod);

            $requestClass = $this->controllerInspector->getRequestClass($controllerClass, $controllerMethod);
            if ($requestClass) {
                $analysis['request'] = $this->requestInspector->inspect($requestClass);
            }

            $analysis['response'] = $this->responseInspector->inspect($controllerClass, $controllerMethod);

            $analysis['example'] = $this->exampleGenerator->generate(
                $uri,
                $method,
                $controllerClass,
                $controllerMethod,
                $requestClass,
            );
        }

        return Response::structured($analysis);
    }
}
