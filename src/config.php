<?php
declare(strict_types=1);

namespace config;

function collection(array $configs = [])
{
    static $collection;

    if ($collection === null) {
        $collection = [];
    }

    if ($configs !== []) {
        $collection = $configs;
    }

    return $collection;
}

/**
 * @param mixed $default
 */
function get(string $key, $default = null)
{
    $config = collection();

    if (\array_key_exists($key, $config)) {
        return $config[$key];
    }

    return \array_reduce(
        \explode('.', $key),
        function ($config, $key) use ($default) {
            return isset($config[$key]) ? $config[$key] : $default;
        },
        $config
    );
}

/**
 * @param mixed $value
 */
function set(string $key, $value)
{
    $replace = \array_reduce(
      \array_reverse(\explode('.', $key)),
      function ($value, $key) {
        return [$key => $value];
      },
      $value
    );

    return collection(\array_replace_recursive(collection(), $replace));
}
