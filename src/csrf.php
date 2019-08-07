<?php
declare(strict_types=1);

namespace csrf;

use LogicException;
use function html\input;
use function crypt\{encrypt, decrypt, secret};
use function request\input as post_parameter;

/**
 * @throws LogicException
 */
function token(): string
{
    if (empty(\session_id())) {
        throw new LogicException('Session is not started');
    }

    return encrypt(\session_id() . secret());
}

function field(): string
{
    return (string) input('hidden', '_CSRF_TOKEN')->value(token());
}

/**
 * @throws LogicException
 */
function check(?string $token = null)
{
    if (empty(\session_id())) {
        throw new LogicException('Session is not started');
    }

    $token = $token ?? post_parameter('_CSRF_TOKEN');
    return \session_id() . secret() === decrypt($token);
}
