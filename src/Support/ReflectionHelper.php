<?php

declare(strict_types=1);

namespace Lmad\Support;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/**
 * Helper static methods for PHP Reflection operations.
 *
 * Extracts method return types, parameters, class file paths,
 * and use statements.
 */
final class ReflectionHelper
{
    /**
     * Returns the method's return type information.
     *
     * Union types are combined as "Type1|Type2".
     * Built-in types have no prefix, class types are returned with "\" prefix.
     *
     * @param  object|string  $class  Class object or fully qualified namespace
     * @param  string  $method  Method name
     * @return string|null Return type name or null
     */
    public static function getMethodReturnType(object|string $class, string $method): ?string
    {
        try {
            $reflection = new ReflectionMethod($class, $method);
            $returnType = $reflection->getReturnType();

            return self::getTypeName($returnType);
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * Returns the method parameters.
     *
     * For each parameter, returns type, nullable status, default value,
     * and variadic information.
     *
     * @param  object|string  $class  Class object or fully qualified namespace
     * @param  string  $method  Method name
     * @return array<array{type: string|null, allows_null: bool, default_value: mixed, is_variadic: bool}>
     */
    public static function getMethodParameters(object|string $class, string $method): array
    {
        try {
            $reflection = new ReflectionMethod($class, $method);
            $parameters = [];

            foreach ($reflection->getParameters() as $parameter) {
                $paramType = self::getTypeName($parameter->getType());

                $parameters[$parameter->name] = [
                    'type' => $paramType,
                    'allows_null' => $parameter->allowsNull(),
                    'default_value' => $parameter->isOptional()
                        ? self::getDefaultValue($parameter)
                        : null,
                    'is_variadic' => $parameter->isVariadic(),
                ];
            }

            return $parameters;
        } catch (\ReflectionException) {
            return [];
        }
    }

    /**
     * Returns the file path where the class is defined.
     *
     * @param  object|string  $class  Class object or fully qualified namespace
     * @return string|null File path or null if not found
     */
    public static function getClassFileName(object|string $class): ?string
    {
        try {
            $reflection = new ReflectionClass($class);

            return $reflection->getFileName();
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * Returns the method's starting line number.
     *
     * @param  object|string  $class  Class object or fully qualified namespace
     * @param  string  $method  Method name
     * @return int|null Line number or null if not found
     */
    public static function getMethodStartLine(object|string $class, string $method): ?int
    {
        try {
            $reflection = new ReflectionMethod($class, $method);

            return $reflection->getStartLine();
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * Checks if a method exists.
     *
     * @param  object|string  $class  Class object or fully qualified namespace
     * @param  string  $method  Method name
     * @return bool True if method exists
     */
    public static function methodExists(object|string $class, string $method): bool
    {
        return method_exists($class, $method);
    }

    /**
     * Extracts use statements from a class.
     *
     * Since PHP reflection doesn't support use statements, it reads
     * the file and parses with regex.
     *
     * @param  object|string  $class  Class object or fully qualified namespace
     * @return array Use statements (imported classes with "use")
     */
    public static function getClassUses(object|string $class): array
    {
        try {
            $reflection = new ReflectionClass($class);
            $fileName = $reflection->getFileName();

            if ($fileName === false || ! file_exists($fileName)) {
                return [];
            }

            $content = file_get_contents($fileName);
            preg_match_all('/^use ([^;]+);$/m', $content, $matches);

            return $matches[1] ?? [];
        } catch (\ReflectionException) {
            return [];
        }
    }

    /**
     * Converts a ReflectionType to string.
     *
     * Union types are merged, named types are normalized.
     *
     * @param  ReflectionType|null  $type  Reflection type object
     * @return string|null Type name or null
     */
    private static function getTypeName(?ReflectionType $type): ?string
    {
        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionUnionType) {
            $types = array_map(
                fn ($t) => $t instanceof ReflectionNamedType ? $t->getName() : (string) $t,
                $type->getTypes()
            );

            return implode('|', $types);
        }

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();

            return $type->isBuiltin() ? $name : '\\'.ltrim($name, '\\');
        }

        return (string) $type;
    }

    /**
     * Returns the parameter's default value.
     *
     * Returns the constant name if it's a constant, otherwise the value.
     *
     * @param  \ReflectionParameter  $parameter  Reflection parameter object
     * @return mixed Default value or constant name
     */
    private static function getDefaultValue(\ReflectionParameter $parameter): mixed
    {
        if (! $parameter->isDefaultValueAvailable()) {
            return null;
        }

        return $parameter->isDefaultValueConstant()
            ? $parameter->getDefaultValueConstantName()
            : $parameter->getDefaultValue();
    }
}
