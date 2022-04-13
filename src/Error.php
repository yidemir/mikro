<?php

declare(strict_types=1);

namespace Error
{
    use Console;
    use Html;
    use Request;
    use Response;

    /**
     * Sets error to exception
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Error\to_exception();
     * ```
     */
    function to_exception(): void
    {
        \set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
    }

    /**
     * Sets default exception handler
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Error\handler();
     * Error\handler(function (\Throwable $e): void {
     *     // handle exception
     * });
     * ```
     */
    function handler(?callable $callback = null): void
    {
        \set_exception_handler($callback ?? function (\Throwable $exception) {
            global $mikro;

            $class = \get_class($exception);

            if (isset($mikro[EXCEPTIONS][$class])) {
                $mikro[EXCEPTIONS][$class]($exception);

                return;
            }

            if (\in_array($class, $mikro[DONT_REPORT] ?? [])) {
                return;
            }

            return response($exception);
        });
    }

    /**
     * Prepare exception response
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Error\handler(function (\Throwable $e) {
     *     // handler
     *
     *     Error\response($e);
     * });
     * ```
     */
    function response(\Throwable $exception): ?int
    {
        $class = \get_class($exception);

        if (\PHP_SAPI === 'cli') {
            Console\error("{$class} with message '{$exception->getMessage()}'");
            Console\write("in {$exception->getFile()}:{$exception->getLine()}");
            Console\write(\str_repeat('-', \strlen($class)));
            Console\write($exception->getTraceAsString());

            return null;
        }

        if (! \error_reporting()) {
            return null;
        }

        if (Request\wants_json()) {
            return Response\json([
                'message' => $exception->getMessage(),
                'data' => [
                    'exception' => $class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTrace()
                ]
            ], 500);
        }

        return Response\html('<!doctype html>' . Html\tag('html', [
            Html\tag('head', [
                Html\tag('title', $class),
                Html\tag('style', 'html { font: .9em/1.5 sans-serif }')
            ]),
            Html\tag('body', Html\tag('div', [
                Html\tag('h1', $class),
                Html\tag('h2', \htmlentities($exception->getMessage())),
                Html\tag('pre', $exception->getTraceAsString())
            ]))
        ]), 500);
    }

    /**
     * Show all errors and exceptions
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Error\show();
     * ```
     */
    function show(): void
    {
        \ini_set('display_errors', '1');
        \ini_set('display_startup_errors', '1');
        \error_reporting(\E_ALL);
    }

    /**
     * Hide all errors and exceptions
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Error\hide();
     * ```
     */
    function hide(): void
    {
        \ini_set('display_errors', '0');
        \error_reporting(0);
    }

    /**
     * Handle spesific exception
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Error\handle(\InvalidArgumentException::class, function (\Throwable $e) {
     *     return Response\html($e->getMessage());
     * });
     *
     * throw new \InvalidArgumentException('Invalid argument');
     * ```
     */
    function handle(string $class, callable $callback): void
    {
        global $mikro;

        $mikro[EXCEPTIONS][$class] = $callback;
    }

    /**
     * Do not report spesific exception
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Error\dont_report(Mikro\Exceptions\MikroException::class);
     * ```
     */
    function dont_report(string $class): void
    {
        global $mikro;

        $mikro[DONT_REPORT][] = $class;
    }

    /**
     * Handled exceptions collection constant
     *
     * @internal
     */
    const EXCEPTIONS = 'Error\EXCEPTIONS';

    /**
     * Collection of hidden exceptions
     *
     * @internal
     */
    const DONT_REPORT = 'Error\DONT_REPORT';
};
