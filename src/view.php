<?php
declare(strict_types=1);

namespace view;

use Exception;
use InvalidArgumentException;

function path($path = null): array
{
    static $paths = [];

    if (\is_string($path)) {
        $path = ['default' => $path];
    }

    if ($path !== null && \is_array($path)) {
        $paths = \array_merge($paths, $path);
    }

    return $paths;
}

/**
 * @throws Exception
 */
function render(string $file, array $data = []): ?string
{
    if (\strpos($file, ':') !== false) {
        [$section, $file] = \explode(':', $file, 2);
    } else {
        $section = 'default';
    }

    $paths = path();

    if (!\array_key_exists($section, $paths)) {
        throw new Exception(
            sprintf('%s named view path does not exists', $section)
        );
    }

    $file = \str_replace('.', '/', $file);
    
    if (\is_file($path = $paths[$section] . '/' . $file . '.php')) {
        \ob_start();
        
        if (!empty($data)) {
            \extract($data);
        }

        require $path;

        return \ob_get_clean();
    }

    throw new Exception(\sprintf('"%s" named view file does not exists', $path));
}

/**
 * @param string|null $name
 * @param mixed $data
 *
 * @return array
 */
function blocks($name = null, $data = null)
{
    static $blocks;

    if (\is_null($blocks)) {
        $blocks = [];
    }

    if ($name !== null) {
        $blocks[$name] = $data;
    }

    return $blocks;
}

function start(?string $name = null)
{
    static $block;

    if ($name !== null) {
        $block = $name;
        \ob_start();
    }

    return $block;
}

function stop()
{
    $block = start();

    if ($block === null) {
        return \ob_end_clean();
    }

    blocks($block, \ob_get_clean());
}

function block($name, $default = null)
{
    return blocks()[$name] ?? $default;
}

function set($name, $value)
{
    blocks($name, $value);
}

function get($name, array $args = [])
{
    $block = block($name);

    if ($block === null) {
        throw new Exception(\sprintf('"%s" named view block does not exists', $name));
    }

    if (\is_callable($block)) {
        return \call_user_func_array($block, [$args]);
    }

    return $block;
}

function parent()
{
    $block = start();
    $blocks = blocks();

    if ($block && \array_key_exists($block, $blocks)) {
        return $blocks[$block];
    }
}

function e($string): string
{
    return \htmlentities((string) $string, \ENT_QUOTES);
}
