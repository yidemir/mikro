<?php

namespace view;

function path(?string $path = null): string
{
    static $viewPath = '';

    if ($path !== null) {
        $viewPath = $path;
    }

    return $viewPath;
}

function render(string $file, array $data = []): ?string
{
    if (is_file($path = path() . '/' . $file . '.php')) {
        ob_start();
        if (!empty($data)) extract($data);
        require_once $path;
        return ob_get_clean();
    }
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

    if (is_null($blocks)) {
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
        ob_start();
    }

    return $block;
}

function stop()
{
    $block = start();

    if ($block === null) {
        return ob_end_clean();
    }

    blocks($block, ob_get_clean());
}

function block(string $name, $default = null)
{
    return blocks()[$name] ?? $default;
}