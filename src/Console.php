<?php

declare(strict_types=1);

namespace Console
{
    /**
     * Defines a new console command
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Console\command('cache:clear', fn() => Cache\flush());
     * ```
     */
    function command(string $name, callable $callback): void
    {
        global $argv;

        if (\PHP_SAPI === 'cli' && isset($argv[1]) && $argv[1] === $name) {
            \call_user_func_array(
                $callback,
                [\array_values(\array_slice($argv, 2))]
            );
        }
    }

    /**
     * Write command line
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Console\write('message');
     * ```
     */
    function write(string $str): int
    {
        return print($str . \PHP_EOL);
    }

    /**
     * Write command line (green color)
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Console\info('message');
     * ```
     */
    function info(string $str): int
    {
        return write("\e[0;32m{$str}\e[0m");
    }

    /**
     * Write command line (red color)
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Console\error('message');
     * ```
     */
    function error(string $str): int
    {
        return write("\e[0;31m{$str}\e[0m");
    }

    /**
     * Performs operations according to the input
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Console\ask('How old are you?', function ($age) {
     *     Console\info("You are $age");
     * });
     * ```
     */
    function ask(string $question, callable $callback): mixed
    {
        write($question);
        $line = \readline();
        $callback($line);

        return $line;
    }
};
