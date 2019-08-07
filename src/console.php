<?php
declare(strict_types=1);

namespace console;

use Closure;
use InvalidArgumentException;

function collection(array $command = [])
{
    static $collection;

    if ($collection === null) {
        $collection = [];
    }

    if ($command !== []) {
        $collection = \array_merge($collection, $command);
    }

    return $collection;
}

function register(string $command, Closure $callback)
{
    collection([$command => $callback]);
}

function run(array $argv)
{
    $file = \array_shift($argv);
    $command = \array_shift($argv);
    $args = parse($argv);
    $commands = collection();

    if (\array_key_exists($command, $commands)) {
        return \call_user_func_array($commands[$command]['callback'], [$args]);
    } else {
        throw new InvalidArgumentException(\sprintf('Command not exists: %s', $command));
    }
}

function parse(array $args)
{
    $newArgs = [];

    foreach ($args as $arg) {
        if (\preg_match('/^\-{2}(?<key>[\w\d]+)$/', $arg, $matches) >= 1) {
            if (\array_key_exists('key', $matches)) {
                $newArgs[$matches['key']] = null;
            }
        } elseif (\preg_match('/^\-(?<key>\w)$/', $arg, $matches) >= 1) {
            if (\array_key_exists('key', $matches)) {
                $newArgs[$matches['key']] = null;
            }
        } elseif (
            \preg_match(
                '/^\-{2}(?<key>[\w\d-]+)=(?<value>[\w\d-,|üğşçöıÜĞİŞÇÖ]+)$/', 
                $arg,
                $matches
            ) >= 1
        ) {
            if (
                \array_key_exists('key', $matches) && 
                \array_key_exists('value', $matches)
            ) {
                $newArgs[$matches['key']] = $matches['value'];
            }
        } else {
            array_push($newArgs, $arg);
        }
    }

    return $newArgs;
}

function register_framework_commands()
{
    register('cache:clear', function() {
        \cache\flush();

        echo "All cache data cleared.\n";
    });

    register('route:list', function(array $args) {
        $routes = \array_map(function($route){
            $route->middleware = \array_map(function($mw) {
                return !\is_string($mw) ? 'Closure' : $mw;
            }, $route->middleware);

            return [
                \implode('|', $route->methods),
                $route->path,
                \is_string($route->callback) ? $route->callback : 'Closure',
                $route->name,
                \implode('|', $route->middleware)
            ];
        }, \route\collection());
        
        $lengths = [
            'method' => 7,
            'path' => 4,
            'callback' => 8,
            'name' => 4,
            'middleware' => 10
        ];

        foreach ($routes as $route) {
            [$method, $path, $callback, $name, $middleware] = $route;

            if ($lengths['method'] < \strlen($method)) {
                $lengths['method'] = \strlen($method);
            }

            if ($lengths['path'] < \strlen($path)) {
                $lengths['path'] = \strlen($path);
            }

            if ($lengths['callback'] < \strlen($callback)) {
                $lengths['callback'] = \strlen($callback);
            }

            if ($lengths['name'] < \strlen($name)) {
                $lengths['name'] = \strlen($name);
            }

            if ($lengths['middleware'] < \strlen($middleware)) {
                $lengths['middleware'] = \strlen($middleware);
            }
        }

        $mask = "| %-{$lengths['method']}.{$lengths['method']}s ";
        $mask .= "| %-{$lengths['path']}.{$lengths['path']}s ";
        $mask .= "| %-{$lengths['callback']}.{$lengths['callback']}s ";
        $mask .= "| %-{$lengths['name']}.{$lengths['name']}s ";
        $mask .= "| %-{$lengths['middleware']}.{$lengths['middleware']}s |\n";

        \printf($mask, 'Methods', 'Path', 'Callback', 'Name', 'Middleware');
        \printf(
            $mask, 
            \str_repeat('-', $lengths['method']), 
            \str_repeat('-', $lengths['path']), 
            \str_repeat('-', $lengths['callback']), 
            \str_repeat('-', $lengths['name']), 
            \str_repeat('-', $lengths['middleware'])
        );

        foreach ($routes as $route) {
            \printf($mask, ...$route);
        }
    });
}

function running_on_cli(): bool
{
    return \PHP_SAPI == 'cli';
}
