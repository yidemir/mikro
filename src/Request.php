<?php

declare(strict_types=1);

namespace Request
{
    use Helper;

    /**
     * Gets request method
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\method(); // string 'POST'
     * Request\method(); // string 'GET'
     * Request\method(); // string 'DELETE'
     * ```
     */
    function method(): string
    {
        return \filter_input(\INPUT_SERVER, 'REQUEST_METHOD', \FILTER_SANITIZE_ENCODED) ?? 'GET';
    }

    /**
     * Gets request path
     *
     * {@inheritDoc} **Example:**
     * ```php
     * // /posts?id=5
     * Request\path(); // string '/posts'
     * // /admin/foo
     * Request\path(); // string '/admin/foo'
     * ```
     */
    function path(): string
    {
        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $path = ($strpos = \strpos($path, '?')) !== false ? \substr($path, 0, $strpos) : $path;

        return $path;
    }

    /**
     * Gets query string
     *
     * {@inheritDoc} **Example:**
     * ```php
     * // /admin/posts?page=5&order=title
     * Request\query_string(); // string 'page=5&order=title'
     * ```
     */
    function query_string(): string
    {
        return $_SERVER['QUERY_STRING'] ?? '';
    }

    /**
     * Gets query in array
     *
     * {@inheritDoc} **Example:**
     * ```php
     * // /admin/posts?page=5&order=title
     * Request\query(); // array ['page' => 5, 'order' => 'title']
     * ```
     */
    function query(): array
    {
        \parse_str(query_string(), $query);

        return $query;
    }

    /**
     * Gets all request data
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\all(); // array ['foo' => 5, 'bar' => 'baz']
     * ```
     */
    function all(): array
    {
        return \array_merge($_REQUEST, $_FILES, to_array());
    }

    /**
     * Gets request data
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\get('foo'); // string 'bar'
     * Request\get('value', 'default'); // string 'default'
     * ```
     */
    function get(string $key, mixed $default = null): mixed
    {
        return $_REQUEST[$key] ?? $_FILES[$key] ?? to_array()[$key] ?? $default;
    }

    /**
     * Gets request data
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\input('foo'); // string 'bar'
     * Request\input('value', 'default'); // string 'default'
     * ```
     */
    function input(string $key, mixed $default = null): mixed
    {
        return get($key, $default);
    }

    /**
     * Gets request body
     */
    function content(): string
    {
        return \file_get_contents('php://input');
    }

    /**
     * Parse request body to array
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\to_array(); // array
     * ```
     */
    function to_array(): array
    {
        $content = content();

        if (header('Content-Type') === 'application/x-www-form-urlencoded') {
            \parse_str($content, $array);
        } else {
            $array = (array) \json_decode($content);
        }

        return $array;
    }

    /**
     * Gets header
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\header('Content-type'); // string 'text/html'
     * ```
     */
    function header(string $key, mixed $default = null): mixed
    {
        return headers()[$key] ?? $default;
    }

    /**
     * Gets all header data
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\headers(); // array ['Content-type' => '..']
     * ```
     */
    function headers(): array
    {
        return \function_exists('getallheaders') ? \getallheaders() : [];
    }

    /**
     * Determine if the current request is asking for JSON.
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\wants_json(); // bool
     * ```
     */
    function wants_json(): bool
    {
        $header = header('Accept', '');
        $pieces = \explode(',', $header);
        $first = $pieces[0] ?? '';

        return \str_contains($first, '/json') || \str_contains($first, '+json');
    }

    /**
     * Get bearer token.
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\bearer_token(); // token string
     * ```
     */
    function bearer_token(): ?string
    {
        $auth = header('Authorization');

        return $auth ? \preg_replace('/^Bearer /', '', $auth) : null;
    }

    /**
     * Returns certain parameters.
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\only(['param1', 'param2']); // array
     * ```
     */
    function only(array $keys): array
    {
        return Helper\arr(all())->only($keys)->all();
    }

    /**
     * Returns it by excluding certain parameters.
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Request\except(['param3', 'param2']); // array
     * ```
     */
    function except(array $keys): array
    {
        return Helper\arr(all())->except($keys)->all();
    }
}
