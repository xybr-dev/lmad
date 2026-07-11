<?php

declare(strict_types=1);

namespace Lmad\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Resource;
use Lmad\Mcp\Schema\ControllerInspector;

/**
 * MCP Resource for controller information.
 *
 * Provides dynamic access via controller://{class}/{method?} URI template.
 * Returns method details if method is provided, otherwise returns the class method list.
 */
final class ControllerResource extends Resource
{
    /** Resource name */
    protected string $name = 'controller';

    /** Resource description */
    protected string $description = 'Dynamic access to controller information via controller://{class} or controller://{class}/{method} URI template.';

    /** URI template (controller://{class}/{method?}) */
    protected ?string $uriTemplate = 'controller://{class}/{method?}';

    /** MIME type */
    protected ?string $mime = 'application/json';

    /** Meta information */
    protected ?array $meta = [
        'category' => 'discovery',
        'author' => '0xmergen',
    ];

    public function __construct(
        private readonly ControllerInspector $controllerInspector,
    ) {}

    /**
     * Handles the MCP request.
     *
     * Returns method details if method is provided, otherwise returns the
     * list of all public methods in the class.
     *
     * @param  Request  $request  MCP request
     * @return Response|ResponseFactory Controller information or error
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $class = (string) $request->get('class');
        $method = (string) $request->get('method');

        if (empty($class)) {
            return Response::error('Controller class is required. Use controller://{class} or controller://{class}/{method}');
        }

        if (empty($method)) {
            return $this->listControllerMethods($class);
        }

        if (! class_exists($class)) {
            return Response::error("Controller class '{$class}' does not exist.");
        }

        if (! method_exists($class, $method)) {
            return Response::error("Method '{$method}' does not exist in controller '{$class}'.");
        }

        $inspection = $this->controllerInspector->inspect($class, $method);

        return Response::structured($inspection);
    }

    /**
     * Lists all public methods in a controller class.
     *
     * Returns all public methods except magic methods (__construct, __destruct, etc.).
     *
     * @param  string  $class  Controller class name
     * @return Response|ResponseFactory Method list or error
     */
    private function listControllerMethods(string $class): Response|ResponseFactory
    {
        if (! class_exists($class)) {
            return Response::error("Controller class '{$class}' does not exist.");
        }

        $reflection = new \ReflectionClass($class);
        $methods = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isDestructor() || str_starts_with($method->name, '__')) {
                continue;
            }

            $methods[] = [
                'name' => $method->name,
                'return_type' => $method->getReturnType()?->getName(),
                'parameters' => collect($method->getParameters())->map(fn ($p) => [
                    'name' => $p->name,
                    'type' => $p->getType()?->getName(),
                    'optional' => $p->isOptional(),
                ])->all(),
                'start_line' => $method->getStartLine(),
            ];
        }

        return Response::structured([
            'controller' => $class,
            'file_path' => $reflection->getFileName(),
            'methods' => $methods,
        ]);
    }
}
