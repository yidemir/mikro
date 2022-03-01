<?php

declare(strict_types=1);

namespace View
{
    use Mikro\Exceptions\{ViewException, MikroException};

    /**
     * Render view file
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[View\PATH] = 'path/to/views';
     *
     * echo View\render('view_file');
     * echo View\render('view_file', ['foo' => 'bar']);
     * ```
     *
     * @throws MikroException If view path not set on global $mikro array
     * @throws ViewException If view file not found
     */
    function render(string $file, array $data = []): string
    {
        global $mikro;

        if (! isset($mikro[PATH])) {
            throw new MikroException('Please set the view path');
        }

        $path = $mikro[PATH] . \DIRECTORY_SEPARATOR . $file . ($mikro[EXTENSION] ?? '.php');

        if (! \is_file($path)) {
            throw new ViewException('View file not found in: ' . $path);
        }

        \ob_start();

        if (! empty($data)) {
            \extract($data);
        }

        require $path;

        return \ltrim((string) \ob_get_clean());
    }

    /**
     * Escape string
     *
     * {@inheritDoc} **Example:**
     * ```php
     * echo View\e('<script>');
     * ```
     */
    function e(string $string): string
    {
        return \htmlentities((string) $string, \ENT_QUOTES);
    }

    /**
     * Turn on output buffering for view
     *
     * {@inheritDoc} **Example:**
     * ```php
     * View\start('content');
     * ```
     */
    function start(string $name): void
    {
        global $mikro;

        $mikro[ACTUAL_BLOCKS][] = $name;

        \ob_start();
    }

    /**
     * Turn off output buffering and set the view block
     *
     * {@inheritDoc} **Example:**
     * ```php
     * View\stop();
     * ```
     */
    function stop(): void
    {
        global $mikro;

        if (! isset($mikro[ACTUAL_BLOCKS]) || empty($mikro[ACTUAL_BLOCKS])) {
            throw new ViewException('View block not started');
        }

        set(\array_pop($mikro[ACTUAL_BLOCKS]), \ob_get_clean());
    }

    /**
     * Set new view block
     *
     * {@inheritDoc} **Example:**
     * ```php
     * View\set('title', 'Page Title');
     * View\set('count', 5);
     * View\set('input', function (string $name) {
     *     return Html\tag('input')->name($name);
     * });
     * ```
     */
    function set(string $name, mixed $value): void
    {
        global $mikro;

        $mikro[BLOCKS][$name] = $value;
    }

    /**
     * Gets view block
     *
     *
     * {@inheritDoc} **Example:**
     * ```php
     * View\get('title');
     * View\get('count');
     * View\get('input')('title');
     */
    function get(string $name, mixed $default = null): mixed
    {
        global $mikro;

        return $mikro[BLOCKS][$name] ?? ($default instanceof \Closure ? $default() : $default);
    }

    /**
     * View path constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[View\PATH] = '/path/to/views';
     * ```
     */
    const PATH = 'View\PATH';

    /**
     * View extension constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * // default .php
     * $mikro[View\EXTENSION] = '.tpl';
     * ```
     */
    const EXTENSION = 'View\EXTENSION';

    /**
     * View actual block constant
     *
     * @internal
     */
    const ACTUAL_BLOCKS = 'View\ACTUAL_BLOCKS';

    /**
     * View secret constant
     *
     * @internal
     */
    const BLOCKS = 'View\BLOCKS';
};
