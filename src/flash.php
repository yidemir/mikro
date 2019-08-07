<?php
declare(strict_types=1);

namespace flash;

use LogicException;

/**
 * @throws LogicException
 */
function push(string $message, string $type = 'default')
{
    if (empty(\session_id())) {
        throw new LogicException('Session is not started');
    }

    $_SESSION['_FLASH'][$type][] = $message;
}

/**
 * @throws LogicException
 */
function message(string $message)
{
    return push($message, 'message');
}

/**
 * @throws LogicException
 */
function error(string $message)
{
    return push($message, 'error');
}

/**
 * @throws LogicException
 */
function get(string $type = 'default'): array
{
    if (empty(\session_id())) {
        throw new LogicException('Session is not started');
    }

    $messages = $_SESSION['_FLASH'][$type] ?? [];
    unset($_SESSION['_FLASH'][$type]);
    return $messages;
}
