<?php
declare(strict_types=1);

namespace response;

use function view\render;

function output(
    string $content,
    int $code = 200,
    array $headers = []
): int
{
    foreach ($headers as $key => $value) {
        send_header($key, $value);
    }

    \http_response_code($code);

    return print $content;
}

function html(
    string $content,
    int $code = 200,
    array $headers = []
): int
{
    if (!\array_key_exists('Content-Type', $headers)) {
        $headers['Content-Type'] = 'text/html;charset=utf-8';
    }

    return output($content, $code, $headers);
}

function json(
    $content,
    int $code = 200,
    array $headers = []
): int
{
    if (!\array_key_exists('Content-Type', $headers)) {
        $headers['Content-Type'] = 'application/json;charset=utf-8';
    }

    $json = (string) \json_encode($content);

    return output($json, $code, $headers);
}

function text(
    string $content,
    int $code = 200,
    array $headers = []
): int
{
    if (!\array_key_exists('Content-Type', $headers)) {
        $headers['Content-Type'] = 'text/plain;charset=utf-8';
    }

    return output($content, $code, $headers);
}

function redirect(string $to, int $code = 301): void
{
    \http_response_code($code);
    send_header('Location', $to);
}

/**
 * @throws Exception
 */
function view(
    string $file,
    array $data = [],
    int $code = 200,
    array $headers = []
): int
{
    return html(render($file, $data), $code, $headers);
}

function send_header(string $key, string $value, ...$args): void
{
    \header(\sprintf('%s:%s', $key, $value), ...$args);
}
