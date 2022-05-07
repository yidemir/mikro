<?php

declare(strict_types=1);

namespace Router
{
    use Request;
    use Response;
    use Mikro\Exceptions\ValidatorException;

    /**
     * Map route and check match. If route matches run callback
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\map('GET', '/', 'callback');
     * Router\map('GET', '/', 'callback', [$middlewareCallback]);
     * Router\map('GET', '/', 'callback', 'mw_callback_one|mw_callback_two');
     * Router\map(['GET'], '/', function () {});
     * Router\map('GET|POST', '/', 'HomeController::index');
     * Router\map(['GET', 'POST'], '/', fn() => [new HomeController(), 'index']());
     * Router\map('GET|POST|PUT', '/posts/{id:num}', fn() => print('Post ID: ' . Router\parameters('id')));
     * ```
     */
    function map(
        array|string $methods,
        string $path,
        mixed $callback,
        array|string $middleware = []
    ): void {
        global $mikro;

        if (\is_string($methods)) {
            $methods = \explode('|', $methods);
        }

        $path = ($mikro[PREFIX] ?? '') . $path;

        if (\is_string($middleware)) {
            $middleware = \explode('|', $middleware);
        }

        $middleware = \array_merge($mikro[MIDDLEWARE] ?? [], $middleware);
        $requestPath = \rawurldecode(\rtrim(Request\path(), '/') ?: '/');

        if (\in_array(Request\method(), $methods) && $requestPath === $path) {
            goto found;
        }

        $path = \rtrim(parse_path($path), '/');

        if (
            \in_array(Request\method(), $methods) &&
            (\preg_match(\sprintf('@^%s$@i', $path), $requestPath, $params) >= 1) &&
            ($mikro[FOUND] ?? null) !== true
        ) {
            found:
            $mikro[FOUND] = true;

            if (isset($params)) {
                \array_shift($params);
                $mikro[PARAMETERS] = $params;
            }

            $result = \array_reduce(\array_reverse($middleware), function ($stack, $item) {
                return function () use ($stack, $item) {
                    return $item($stack);
                };
            }, $callback);

            \call_user_func($result);
        }
    }

    /**
     * Parse route parameters
     *
     * @internal
     */
    function parse_path(string $path): string
    {
        if (\preg_match('/(\/{.*}\?)/i', $path, $matches)) {
            foreach (\range(1, \count($matches)) as $match) {
                $path = \preg_replace('/\/({.*}\?)/', '/?$1', $path);
            }
        }

        \preg_replace_callback('/[\[{\(].*[\]}\)]/U', function ($match) use (&$path): string {
            $match = \str_replace(['{', '}'], '', $match[0]);

            if (\str_contains($match, ':')) {
                [$name, $type] = \explode(':', $match, 2);
            } else {
                $name = $match;
                $type = 'any';
            }

            $patterns = [
                'num' => '(?<name>\d+)',
                'str' => '(?<name>[\w\-_]+)',
                'any' => '(?<name>[^/]+)',
                'all' => '(?<name>.*)',
            ];
            $replaced = \str_replace('name', $name, ($patterns[$type] ?? $patterns['any']));
            $path = \str_replace("{{$name}:$type}", $replaced, $path);
            $path = \str_replace("{{$name}}", $replaced, $path);

            return $path;
        }, $path);

        return $path;
    }

    /**
     * Maps the GET route
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\get('/', 'callback');
     * Router\get('/', 'callback', $middlewareArray);
     * Router\get('/', function () {
     *     return Response\view('home');
     * });
     * ```
     */
    function get(string $path, mixed $callback, array|string $middleware = []): void
    {
        map('GET', $path, $callback, $middleware);
    }

    /**
     * Maps the POST route
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\post('/save', 'callback');
     * Router\post('/', 'callback', $middlewareArray);
     * Router\post('/posts', function () {
     *     return DB\insert('posts', ['title' => Request\get('title')]);
     * });
     * ```
     *
     */
    function post(string $path, mixed $callback, array|string $middleware = []): void
    {
        map('POST', $path, $callback, $middleware);
    }

    /**
     * Maps the PATCH route
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\patch('/update', 'callback');
     * Router\patch('/update', 'callback', $middlewareArray);
     * ```
     */
    function patch(string $path, mixed $callback, array|string $middleware = []): void
    {
        map('PATCH', $path, $callback, $middleware);
    }

    /**
     * Maps the PUT route
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\put('/update', 'callback');
     * Router\put('/update', 'callback', $middlewareArray);
     * ```
     */
    function put(string $path, mixed $callback, array|string $middleware = []): void
    {
        map('PUT', $path, $callback, $middleware);
    }

    /**
     * Maps the DELETE route
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\delete('/destroy', 'callback');
     * Router\delete('/destroy', 'callback', $middlewareArray);
     * ```
     */
    function delete(string $path, mixed $callback, array|string $middleware = []): void
    {
        map('DELETE', $path, $callback, $middleware);
    }

    /**
     * Maps the OPTIONS route
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\options('/', 'callback');
     * Router\options('/', 'callback', $middlewareArray);
     * ```
     */
    function options(string $path, mixed $callback, array|string $middleware = []): void
    {
        map('OPTIONS', $path, $callback, $middleware);
    }

    /**
     * Maps the any route
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\any('/{anything:any}', 'callback');
     * Router\any('/{anything:any}', 'callback', $middlewareArray);
     * ```
     */
    function any(string $path, mixed $callback, array|string $middleware = []): void
    {
        map('GET|POST|PATCH|PUT|DELETE', $path, $callback, $middleware);
    }

    /**
     * Maps the view route
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\view('/page', 'templates/page');
     * Router\view('/page', 'templates/page', ['with' => 'data']);
     * ```
     */
    function view(string $path, string $file, array $data = [], array|string $middleware = []): void
    {
        any($path, fn() => Response\view($file, $data), $middleware);
    }

    /**
     * Maps the redirect route
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\redirect('/page', '/new/page/url');
     * Router\redirect('/other-page', 'https://url');
     * ```
     */
    function redirect(string $path, string $to, array|string $middleware = []): void
    {
        any($path, fn() => Response\redirect($to), $middleware);
    }

    /**
     * Checks route is match. It must be located at the end of all route definitions.
     *
     * {@inheritDoc} **Example:**
     * ```php
     * if (! Router\is_found()) {
     *     echo '404 not found!';
     * }
     * ```
     */
    function is_found(): bool
    {
        global $mikro;

        return isset($mikro[FOUND]) && $mikro[FOUND] === true;
    }

    /**
     * Define 404 error route. It must be located at the end of all route definitions.
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\error('callback');
     * Router\error(function () {
     *     Response\html('404 not found!', Response\STATUS['HTTP_NOT_FOUND']);
     * });
     * Router\error([
     *     fn() => Response\html('Default 404 error handler'),
     *     '/posts' => fn() => Response\html('/posts 404 error handler'),
     *     '/products' => fn() => 'ProductController::notFoundHandler',
     * ])
     * ```
     */
    function error(mixed $callback = []): void
    {
        if (! \is_array($callback)) {
            $callback = [$callback];
        }

        if (! is_found()) {
            \http_response_code(404);
            $path = Request\path();

            foreach ($callback as $key => $_callback) {
                $starts = \str_starts_with($path, (string) $key);
                $match = \preg_match('@' . (string) $key . '@', $path);

                if (! empty($key) && ($starts || $match)) {
                    if (! \is_callable($_callback)) {
                        throw new ValidatorException("Error callback `$key` is not valid");
                    }

                    \call_user_func($_callback);

                    return;
                }
            }

            if (isset($callback[0]) && \is_callable($callback[0])) {
                \call_user_func($callback[0]);
            }
        }
    }

    /**
     * Group routes
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Route\group('/admin', function () {
     *     Route\get('', 'Admin\HomeController::index');
     *
     *     Route\group('/posts', function () {
     *         Route\get('', 'Admin\PostController::index');
     *         Route\get('/{id:num}', 'Admin\PostController::show');
     *     });
     * });
     * Router\group('/prefix', fn() => [
     *     Router\get('/home', 'home_callback')
     * ], $middlewareArray);
     * ```
     */
    function group(string $prefix, mixed $callback, array|string $middleware = []): void
    {
        global $mikro;

        if (\is_string($middleware)) {
            $middleware = \explode('|', $middleware);
        }

        if (! isset($mikro[PREFIX])) {
            $mikro[PREFIX] = '';
        }

        if (! isset($mikro[MIDDLEWARE])) {
            $mikro[MIDDLEWARE] = [];
        }

        $mikro[PREFIX] .= $prefix;
        $mikro[MIDDLEWARE] = \array_merge($mikro[MIDDLEWARE], $middleware);

        \call_user_func($callback);

        if (($pos = \strrpos($mikro[PREFIX], $prefix)) !== false) {
            $mikro[PREFIX] = \substr_replace($mikro[PREFIX], '', $pos, \strlen($prefix));
        }

        foreach ($middleware as $mw) {
            \array_pop($mikro[MIDDLEWARE]);
        }
    }

    /**
     * Get route parameter(s)
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Route\get('/posts/{postTitle}', function () {
     *     Router\parameters(); // ['postTitle' => 'value']
     *     Router\parameters('postTitle') => 'value'
     * });
     *
     * Route\get('/posts/{postId:num}', function () {
     *     Router\parameters(); // ['postId' => '5']
     *     Router\parameters('postId') => '5'
     * });
     * ```
     *
     * Available options: num, str, any, all
     *
     * /posts/{post:num} => /posts/5
     * /posts/{post:str} => /posts/lorem-lipsum-dolor
     * /posts/{post:any} => /posts/lorem-lipsum_^54-any-char
     * /posts/{post:all} => /posts/any-char/any-slash
     * /posts/{post} => if no option, equals any option
     */
    function parameters(?string $name = null, mixed $default = null): mixed
    {
        global $mikro;

        return $name === null ?
            $mikro[PARAMETERS] ?? [] :
            ($mikro[PARAMETERS][$name] ?? $default);
    }

    /**
     * Create resource routes
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\resource('/posts', 'Controllers\PostController');
     * Router\resource('/items', 'Controllers\ItemController:index|show'); // only index and show
     * Router\resource('/foo', FooController::class, ['middleware_one']);
     * ```
     */
    function resource(string $path, string $class, array|string $middleware = []): void
    {
        $only = null;

        if (\str_contains($class, ':')) {
            [$class, $only] = \explode(':', $class, 2);
        }

        $call = fn(string $method) =>
            (\is_callable("{$class}::{$method}") ?
                "{$class}::{$method}" : fn() => \call_user_func([new $class(),  $method]));

        $methods = [
            'index' => fn() => get($path, $call('index'), $middleware),
            'store' => fn() => post($path, $call('store'), $middleware),
            'create' => fn() => get("{$path}/create", $call('create'), $middleware),
            'show' => fn() => get("{$path}/{id}", $call('show'), $middleware),
            'edit' => fn() => get("{$path}/{id}/edit", $call('edit'), $middleware),
            'update' => fn() => put("{$path}/{id}", $call('update'), $middleware),
            'destroy' => fn() => delete("{$path}/{id}", $call('destroy'), $middleware),
        ];

        if ($only) {
            foreach (\explode('|', $only) as $method) {
                if (isset($methods[$method])) {
                    $methods[$method]();
                }
            }
        } else {
            foreach ($methods as $method) {
                $method();
            }
        }
    }

    /**
     * Sync routes with files in specific directory
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Router\files('/', __DIR__ . '/pages/front');
     * Router\files('/admin', __DIR__ . '/pages/back', ['AdminMiddleware::handle']);
     * ```
     */
    function files(string $routePath, string $filesPath, array|string $middleware = []): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filesPath)
        );

        foreach ($iterator as $item) {
            if ($item->isDir() || ! \str_ends_with($item->getFilename(), '.php')) {
                continue;
            }

            $route = resolve_file($item, \realpath($filesPath));

            map(
                [$route['method']],
                '/' . \trim($routePath . $route['path'], '/'),
                fn() => require($item->getPathName()),
                $middleware
            );
        }
    }

    /**
     * Resolve file for route
     *
     * @internal
     */
    function resolve_file(\SplFileInfo $file, string $base): array
    {
        $prefix = \str_replace([$base, \DIRECTORY_SEPARATOR], ['', '/'], \dirname($file->getRealPath()));
        $method = 'GET';
        $path = $file->getBaseName('.php');

        foreach (['get', 'post', 'put', 'patch', 'delete', 'options'] as $item) {
            if (\str_ends_with($file->getBaseName('.php'), $item)) {
                $method = \strtoupper($item);
                $path = $file->getBaseName('.' . $item . '.php');
            }
        }

        $path = $path === 'index' ? '' : $path;
        $path = \rtrim($prefix . '/' . $path, '/') ?: '/';

        return \compact('method', 'path');
    }

    /**
     * Router found constant
     *
     * @internal
     */
    const FOUND = 'Router\FOUND';

    /**
     * Router prefix constant
     *
     * @internal
     */
    const PREFIX = 'Router\PREFIX';

    /**
     * Router middleware constant
     *
     * @internal
     */
    const MIDDLEWARE = 'Router\MIDDLEWARE';

    /**
     * Router parameters
     *
     * @internal
     */
    const PARAMETERS = 'Router\PARAMETERS';
}
