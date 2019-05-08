<?php
declare(strict_types=1);

namespace log;

function path(?string $path = null): string
{
    static $logPath = '';

    if ($path !== null) {
        $logPath = $path;
    }

    return $logPath;
}

function write(string $type, $data)
{
    if (!\is_string($data) && $data !== null) {
        $data = \json_encode($data);
    }

    $line = "[%s] %s: %s";
    $line = \sprintf($line, \date('Y-m-d H:i:s'), \mb_strtoupper($type), (string) $data);
    $path = \sprintf('%s/%s.log', path(), \date('Y-m-d'));
    @\file_put_contents($path, $line . "\n", \FILE_APPEND);
}

function error($data)
{
    return write('error', $data);
}

function info($data)
{
    return write('info', $data);
}

function debug($data)
{
    return write('debug', $data);
}
