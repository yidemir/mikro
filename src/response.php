<?php
declare(strict_types=1);

namespace response;

use function view\render;

function output(
    string $content,
    int $code = 200,
    string $contentType = 'text/html;charset=utf-8'
): int
{
    \header('Content-type: ' . $contentType, true, $code);
    return print $content;
}

function html(
    string $content,
    int $code = 200,
    string $contentType = 'text/html;charset=utf-8'
): int
{
    return output($content, $code, $contentType);
}

function json(
    $content,
    int $code = 200,
    string $contentType = 'application/json;charset=utf-8'
): int
{
    $jsonString = (string) \json_encode($content);
    return output($jsonString, $code, $contentType);
}

function redirect(string $to): void
{
    header('Location: ' . $to);
}

function view(string $file, array $data = []): int
{
    return html(render($file, $data));
}
