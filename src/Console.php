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
     * // php console.php cache:clear
     *
     * Console\command('say:hello', function (array $args) {
     *     if (isset($args[0])) {
     *         return Console\write("Hello {$args[0]}!");
     *     }
     *
     *      Console\write('Hello world!');
     * });
     *
     * // php console.php say:hello // prints "Hello world!"
     * // php console.php say:hello Foo // prints "Hello Foo!"
     * ```
     */
    function command(string $name, callable $callback): mixed
    {
        global $argv;

        if (\PHP_SAPI === 'cli' && isset($argv[1]) && $argv[1] === $name) {
            $args = \array_values(\array_slice($argv, 2));

            foreach ($args as $arg) {
                if (\str_starts_with($arg, '-')) {
                    $pieces = \explode('=', \preg_replace('/^-{1,2}(\w+)/', '$1', $arg));

                    if (\str_starts_with($arg, '--')) {
                        $args[$pieces[0]]  = $pieces[1] ?? false;
                    } else {
                        foreach (\str_split($pieces[0]) as $letter) {
                            $args[$letter] = false;
                        }
                    }
                }
            }

            return \call_user_func_array($callback, [$args]);
        }

        return null;
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
        return print($str . "\n");
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
    function ask(string $question, ?callable $callback = null): mixed
    {
        write($question);
        $line = \readline();

        if ($callback) {
            \call_user_func_array($callback, [$line]);
        }

        return $line;
    }
}
