<?php

namespace Validator
{
    use Request;

    /**
     * Validate array simple way
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Validator\validate(Request\all(), 'title', 'isset|!empty'); // required and filled
     * $callback = fn($value, $data) => isset($data['title']) && ! empty($value);
     * Validator\validate($_POST, 'title', [$callback]); // required and filled
     * Validator\valdiate(Request\all(), 'username', [$callback, 'ctype_alnum']);
     * Validator\validate(Request\all(), 'email', 'filter_var:' . FILTER_VALIDATE_EMAIL);
     * ```
     */
    function validate(array $data, string $key, string|array|callable $rules): bool
    {
        // Split $rules is a string
        if (\is_string($rules)) {
            $rules = \explode('|', $rules);
        }

        // Cast $rules to array if that is a callable
        if (\is_callable($rules)) {
            $rules = [$rules];
        }

        // Normalize rule
        $normalizeRule = function (string $rule) {
            $normalizedRule = [];

            // If data contains "!" character, set "not" key to true
            $normalizedRule['not'] = \str_contains($rule, '!') ? true : false;

            // If the rule contains the ":"
            if (\str_contains($rule, ':')) {
                // Split rule and parameters
                [$rule, $stringParameters] = \explode(':', $rule, 2);
                // Split parameters
                $parameters = \explode(',', $stringParameters);

                // Set splitted parameters
                $normalizedRule['parameters'] = $parameters;
            } else {
                // Parameter is not defined
                $normalizedRule['parameters'] = [];
            }

            // Normalize the rule. The rules can also have callback functions.
            $normalizedRule['rule'] = \str_replace('!', '', $rule);

            return $normalizedRule;
        };

        $pass = \array_map(function ($rule) use ($normalizeRule, $data, $key) {
            if (\is_string($rule)) {
                $rule = $normalizeRule($rule);

                // isset and empty functions are not callback in PHP
                $result = match ($rule['rule']) {
                    'isset' => isset($data[$key]),
                    'empty' => empty($data[$key] ?? ''),
                    default => \call_user_func_array(
                        $rule['rule'],
                        \array_merge([$data[$key] ?? null], $rule['parameters'])
                    )
                };
            } elseif (\is_callable($rule)) {
                $result = \call_user_func_array($rule, [$data[$key] ?? null, $data]);
            } else {
                return false;
            }

            return (\is_array($rule) && (isset($rule['not']) ? $rule['not'] : false)) ?
                ! $result : $result;
        }, $rules);

        return empty(\array_filter($pass, fn($item) => $item === false));
    }

    /**
     * Validate array and get all results in array
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Validator\validate_all(
     *     ['title' => 'foo', 'email' => 'bar'],
     *     [
     *        'title' => 'isset|!empty|ctype_alnum',
     *        'email' => 'isset|!empty|filter_var:' . FILTER_VALIDATE_EMAIL
     *    ]
     * ); // ['title' => true, 'email' => false]
     * ```
     */
    function validate_all(array $data, array $rules): array
    {
        $results = \array_map(function ($key, $rule) use ($data) {
            return validate($data, $key, $rule);
        }, \array_keys($rules), \array_values($rules));

        return \array_combine(\array_keys($rules), \array_values($results));
    }

    /**
     * Validate array and get result in boolean
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Validator\is_validate_all(
     *     ['title' => 'foo', 'email' => 'bar'],
     *     [
     *        'title' => 'isset|!empty|ctype_alnum',
     *        'email' => 'isset|!empty|filter_var:' . FILTER_VALIDATE_EMAIL
     *    ]
     * ); // true|false
     * ```
     */
    function is_validated_all(array $data, array $rules): bool
    {
        $results = \array_map(function ($key, $rule) use ($data) {
            return validate($data, $key, $rule);
        }, \array_keys($rules), \array_values($rules));

        return empty(\array_filter($results, fn($result) => $result === false));
    }

    /**
     * Validate request data and get all results in array
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Validator\validate_request([
     *     'title' => 'isset|!empty|ctype_alnum',
     *     'email' => 'isset|!empty|filter_var:' . FILTER_VALIDATE_EMAIL
     * ]); // ['title' => true, 'email' => false]
     * ```
     */
    function validate_request(array $rules): array
    {
        return validate_all(Request\all(), $rules);
    }
};
