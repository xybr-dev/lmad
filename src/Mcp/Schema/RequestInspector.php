<?php

declare(strict_types=1);

namespace Lmad\Mcp\Schema;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Contracts\Validation\ValidationRule;
use Lmad\Support\ReflectionHelper;

/**
 * Inspects and analyzes FormRequest classes.
 *
 * Extracts validation rules, custom error messages, attribute names,
 * and authorization logic.
 */
final class RequestInspector
{
    /**
     * Inspects the FormRequest class.
     *
     * Returns rules, messages, attributes, and authorization information.
     *
     * @param  string  $requestClass  FormRequest class name (base64 encoded or normal)
     * @return array{class: string, file_path: string|null|false, rules: array, messages: array, attributes: array, authorization: array}|array{error: string}
     */
    public function inspect(string $requestClass): array
    {
        $requestClass = $this->normalizeClassName($requestClass);

        if (! class_exists($requestClass)) {
            return [
                'error' => "Request class '{$requestClass}' does not exist.",
            ];
        }

        return [
            'class' => $requestClass,
            'file_path' => ReflectionHelper::getClassFileName($requestClass),
            'rules' => $this->extractRules($requestClass),
            'messages' => $this->extractMessages($requestClass),
            'attributes' => $this->extractAttributes($requestClass),
            'authorization' => $this->inspectAuthorization($requestClass),
        ];
    }

    /**
     * Extracts validation rules from the FormRequest rules method.
     *
     * Returns rules in normalized format.
     *
     * @param  string  $requestClass  FormRequest class name
     * @return array Normalized validation rules
     */
    public function extractRules(string $requestClass): array
    {
        if (! ReflectionHelper::methodExists($requestClass, 'rules')) {
            return [];
        }

        $method = new \ReflectionMethod($requestClass, 'rules');
        $method->setAccessible(true);
        $request = (new \ReflectionClass($requestClass))->newInstanceWithoutConstructor();
        $rules = $method->invoke($request);

        return $this->normalizeRules($rules);
    }

    /**
     * Extracts custom error messages from the FormRequest messages method.
     *
     * @param  string  $requestClass  FormRequest class name
     * @return array Custom error messages
     */
    public function extractMessages(string $requestClass): array
    {
        if (! ReflectionHelper::methodExists($requestClass, 'messages')) {
            return [];
        }

        $method = new \ReflectionMethod($requestClass, 'messages');
        $method->setAccessible(true);
        $request = (new \ReflectionClass($requestClass))->newInstanceWithoutConstructor();

        return $method->invoke($request);
    }

    /**
     * Extracts custom attribute names from the FormRequest attributes method.
     *
     * @param  string  $requestClass  FormRequest class name
     * @return array Custom attribute names
     */
    public function extractAttributes(string $requestClass): array
    {
        if (! ReflectionHelper::methodExists($requestClass, 'attributes')) {
            return [];
        }

        $method = new \ReflectionMethod($requestClass, 'attributes');
        $method->setAccessible(true);
        $request = (new \ReflectionClass($requestClass))->newInstanceWithoutConstructor();

        return $method->invoke($request);
    }

    /**
     * Inspects the FormRequest authorize method.
     *
     * Returns the result if the method exists and is callable.
     *
     * @param  string  $requestClass  FormRequest class name
     * @return array{has_authorize: bool, authorized: bool}|array{has_authorize: true, authorized: bool, type: string}|array{has_authorize: true, error: string}
     */
    private function inspectAuthorization(string $requestClass): array
    {
        if (! ReflectionHelper::methodExists($requestClass, 'authorize')) {
            return [
                'has_authorize' => false,
                'authorized' => true,
            ];
        }

        $method = new \ReflectionMethod($requestClass, 'authorize');
        $method->setAccessible(true);
        $request = (new \ReflectionClass($requestClass))->newInstanceWithoutConstructor();

        try {
            $authorized = $method->invoke($request);

            return [
                'has_authorize' => true,
                'authorized' => $authorized,
                'type' => is_bool($authorized) ? 'boolean' : 'gate/policy',
            ];
        } catch (\Exception $e) {
            return [
                'has_authorize' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalizes the class name (base64 decode or path correction).
     *
     * @param  string  $requestClass  Raw class name
     * @return string Normalized class name
     */
    private function normalizeClassName(string $requestClass): string
    {
        $decoded = base64_decode($requestClass, true);
        if ($decoded && class_exists($decoded)) {
            return $decoded;
        }

        $requestClass = strtr($requestClass, '/', '\\');
        $requestClass = str_replace('\\\\', '\\', $requestClass);

        return $requestClass;
    }

    /**
     * Converts validation rules to normalized format.
     *
     * Parses rules in string ("required|string|max:255") or array format.
     *
     * @param  array  $rules  Raw validation rules
     * @return array Normalized rules
     */
    private function normalizeRules(array $rules): array
    {
        $normalized = [];

        foreach ($rules as $field => $fieldRules) {
            $normalized[$field] = $this->parseFieldRules($fieldRules);
        }

        return $normalized;
    }

    /**
     * Parses rules for a single field.
     *
     * @param  mixed  $fieldRules  Rules in string or array format
     * @return array Parsed rules
     */
    private function parseFieldRules(mixed $fieldRules): array
    {
        if (is_string($fieldRules)) {
            return $this->parseRuleString($fieldRules);
        }

        if (is_array($fieldRules)) {
            return array_map(fn ($rule) => $this->parseRule($rule), $fieldRules);
        }

        return [];
    }

    /**
     * Parses a pipe-separated rule string.
     *
     * @param  string  $rules  String in "required|string|max:255" format
     * @return array Parsed rules
     */
    private function parseRuleString(string $rules): array
    {
        $parsed = [];
        $ruleParts = explode('|', $rules);

        foreach ($ruleParts as $rule) {
            $parsed[] = $this->parseSingleRule($rule);
        }

        return $parsed;
    }

    /**
     * Parses a single rule based on its type.
     *
     * Rule can be a Rule object, ValidationRule, or string.
     *
     * @param  mixed  $rule  Rule (RuleContract|ValidationRule|string)
     * @return array Parsed rule
     */
    private function parseRule(mixed $rule): array
    {
        return match (true) {
            $rule instanceof RuleContract => $this->parseRuleObject($rule),
            $rule instanceof ValidationRule => $this->parseValidationRule($rule),
            is_string($rule) => $this->parseSingleRule($rule),
            default => ['rule' => 'unknown', 'raw' => $rule],
        };
    }

    /**
     * Parses a RuleContract implementation.
     *
     * @param  Rule  $rule  Rule object
     * @return array{rule: string, class: class-string}
     */
    private function parseRuleObject(RuleContract $rule): array
    {
        return [
            'rule' => 'object',
            'class' => $rule::class,
        ];
    }

    /**
     * Parses a ValidationRule implementation.
     *
     * @param  ValidationRule  $rule  ValidationRule object
     * @return array{rule: string, class: class-string}
     */
    private function parseValidationRule(ValidationRule $rule): array
    {
        return [
            'rule' => 'validation_rule',
            'class' => $rule::class,
        ];
    }

    /**
     * Parses a single rule in string format.
     *
     * Parses rules like "required", "max:255", "in:a,b,c" into
     * rule name and parameters.
     *
     * @param  string  $rule  Rule string
     * @return array{rule: string, parameters: array}
     */
    private function parseSingleRule(string $rule): array
    {
        if (! str_contains($rule, ':')) {
            return [
                'rule' => $rule,
                'parameters' => [],
            ];
        }

        [$ruleName, $parameters] = explode(':', $rule, 2);

        return [
            'rule' => $ruleName,
            'parameters' => explode(',', $parameters),
        ];
    }
}
