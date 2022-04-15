<?php

declare(strict_types=1);

namespace Error
{
    use Console;
    use Html;
    use Request;
    use Response;
    use Logger;

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

        \set_exception_handler(function (\Throwable $exception) use ($callback) {
            global $mikro;

            $class = \get_class($exception);

            if (\in_array($class, $mikro[DONT_REPORT] ?? [])) {
                return;
            }

            if (isset($mikro[LOG][$class]) || isset($mikro[LOG]['*'])) {
                Logger\log(
                    $mikro[LOG][$class] ?? $mikro[LOG]['*'],
                    $exception->getMessage(),
                    $exception->getTrace()
                );
            }

            if (isset($mikro[EXCEPTIONS][$class])) {
                $specific = $mikro[EXCEPTIONS][$class]($exception);

                if ($specific) {
                    return $specific;
                }
            }

            if ($callback) {
                $callback = $callback($exception);

                if ($callback) {
                    return $callback;
                }
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
            Console\write("in {$exception->getFile()} line {$exception->getLine()}");
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
                Html\tag('style', '
                    html { font: .9em/1.5 sans-serif }
                    div.exception-wrapper { width: 50%; margin: 0 auto }
                    h1.exception-title { color: gray; margin: 0 }
                    h2.exception-message { margin: 0 }
                    h3.exception-file { color: gray; margin: 0 }
                ')
            ]),
            Html\tag('body', Html\tag('div', [
                Html\tag('h1', $class)->class('exception-title'),
                Html\tag('h2', \htmlentities($exception->getMessage()))->class('exception-message'),
                Html\tag('h3', "in {$exception->getFile()} line {$exception->getLine()}")->class('exception-file'),
                Html\tag('pre', $exception->getTraceAsString())->class('exception-trace')
            ])->class('exception-wrapper'))
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
     * Log spesific exception with specific log level
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Error\log(PDOException::class, Logger\LEVEL_CRITICAL);
     * ```
     */
    function log(string $exception = '*', string $logLevel = Logger\LEVEL_DEBUG): void
    {
        global $mikro;

        $mikro[LOG][$exception] = $logLevel;
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

    /**
     * Collection of exception logger
     *
     * @internal
     */
    const LOG = 'Error\LOG';
};
