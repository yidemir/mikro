<?php
declare(strict_types=1);

namespace lang;

function path(?string $path = null): string
{
    static $langPath = '';

    if ($path !== null) {
        $langPath = $path;
    }

    return $langPath;
}

function lang(?string $code = null)
{
    static $language;

    if ($code !== null) {
        $language = $code;
    }

    return $language ?? 'tr';
}

function get(string $key, $default = null)
{
    $dots = \explode('.', $key);
    $file = \array_shift($dots);
    $path = \sprintf('%s/%s/%s.php', path(), lang(), $file);

    if (is_file($path)) {
        static $loaded = [];

        if (\array_key_exists($file, $loaded)) {
            $array = $loaded[$file];
        } else {
            $loaded[$file] = require $path;
            $array = $loaded[$file];
        }

        $dotString = \implode('.', $dots);

        if (\array_key_exists($dotString, $array)) {
            return $array[$dotString];
        }

        return \array_reduce(
            $dots,
            function ($config, $key) use ($default) {
                return isset($config[$key]) ? $config[$key] : $default;
            },
            $array
        );
    }

    return $default;
}

function phrase(string $phrase, ?string $file = null)
{
    if ($file === null) {
        $file = lang();
    }

    $path = \sprintf('%s/%s/%s.php', path(), lang(), $file);

    if (\is_file($path)) {
        static $loaded = [];

        if (\array_key_exists($file, $loaded)) {
            $array = $loaded[$file];
        } else {
            $loaded[$file] = require $path;
            $array = $loaded[$file];
        }

        return $array[$phrase] ?? $phrase;
    }

    return $phrase;
}
