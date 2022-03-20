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
            \extract($data, \EXTR_SKIP);
        }

        require cache($path);

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
    function stop(bool $push = false): void
    {
        global $mikro;

        if (! isset($mikro[ACTUAL_BLOCKS]) || empty($mikro[ACTUAL_BLOCKS])) {
            throw new ViewException('View block not started');
        }

        $actual = \array_pop($mikro[ACTUAL_BLOCKS]);

        set($actual, $push ? (get($actual) ?? '') . \ob_get_clean() : \ob_get_clean());
    }

    /**
     * Turn off output buffering and push/append the view block data
     *
     * {@inheritDoc} **Example:**
     * ```php
     * View\stop();
     * ```
     */
    function push(): void
    {
        stop(true);
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

    function cache(string $path): string
    {
        global $mikro;

        $cachePath = $mikro[CACHE_PATH] ?? $mikro[PATH] . '/cache';

        if (! \file_exists($cachePath)) {
            \mkdir($cachePath, 0744);
        }

        $cachedPath = $cachePath . '/' . \md5($path) . '.php';

        if (! \is_file($cachedPath) || \filemtime($cachedPath) < \filemtime($path)) {
            $contents = template(\file_get_contents($path));
            \file_put_contents($cachedPath, $contents);
        }

        return $cachedPath;
    }

    function template(string $data): string
    {
        global $mikro;

        if (
            isset($mikro[DELIMITER]) &&
            \is_array($mikro[DELIMITER]) &&
            \count($mikro[DELIMITER]) === 2
        ) {
            [$start, $end] = $mikro[DELIMITER];
        } else {
            [$start, $end] = ['@', ';'];
        }

        $data = \preg_replace_callback(
            "~(?!<\?php.*){$start}=(.+?[^\\\;]){$end}(?!.*\?>)~m",
            function (array $matches) use ($end) {
                $data = \str_replace('\\' . $end, $end, $matches[1]);

                return '<?php echo \View\e(' . $data . ') ?>';
            },
            $data
        );

        return \preg_replace_callback(
            "~(?!<\?php.*){$start}(.+?[^\\\;]){$end}(?!.*\?>)~m",
            function ($matches) use ($mikro, $end) {
                $data = \str_replace('\\' . $end, $end, $matches[1]);

                foreach (($mikro[METHODS] ?? []) as $method => $callback) {
                    if (\str_starts_with($data, $method . ' ') || $method === $data) {
                        $data = \preg_replace('/' . $method . ' /', '', $data, 1);

                        return $callback(\str_replace('\\' . $end, $end, $data));
                    }
                }

                return '<?php ' . $data . ' ?>';
            },
            $data
        );
    }

    function method(string $method, callable $callback): void
    {
        global $mikro;

        $mikro[METHODS][$method] = $callback;
    }

    function clear(): void
    {
        global $mikro;

        foreach (\glob($mikro[CACHE_PATH] ?? $mikro[PATH] . '/cache/*.php') as $file) {
            \unlink($file);
        }
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
     * View template cache path
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[View\CACHE_PATH] = __DIR__ . '/views/cache';
     * ```
     */
    const CACHE_PATH = 'View\CACHE_PATH';

    /**
     * View renderer delimiter
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[View\DELIMITER] = ['@', ';'];
     * ```
     */
    const DELIMITER = 'View\DELIMITER';

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

    /**
     * View renderer methods
     *
     * @internal
     */
    const METHODS = 'View\METHODS';
};
