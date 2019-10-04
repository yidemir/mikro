<?php
declare(strict_types=1);

namespace jwt;

use DomainException;
use UnexpectedValueException;
use function crypt\secret as crypt_secret;

function secret(?string $secret = null): string
{
    static $jwtSecret = '';

    if ($secret !== null) {
        $jwtSecret = $secret;
    }

    if ($jwtSecret === '') {
        $jwtSecret = crypt_secret();
    }

    return $jwtSecret;
}

function decode(string $jwt, bool $verify = true): object
{
    $tks = explode('.', $jwt);

    if (count($tks) !== 3) {
        throw new UnexpectedValueException('Wrong number of segments');
    }

    [$headb64, $payloadb64, $cryptob64] = $tks;

    if (null === ($header = json_decode(url_safe_base64_decode($headb64)))) {
        throw new UnexpectedValueException('Invalid segment encoding');
    }

    if (null === ($payload = json_decode(url_safe_base64_decode($payloadb64)))) {
        throw new UnexpectedValueException('Invalid segment encoding');
    }

    $sig = url_safe_base64_decode($cryptob64);

    if ($verify) {
        if (empty($header->alg)) {
            throw new DomainException('Empty algorithm');
        }
        if ($sig != sign("$headb64.$payloadb64", $header->alg)) {
            throw new UnexpectedValueException('Signature verification failed');
        }
    }

    return $payload;
}

/**
 * @param object|array $payload
 */
function encode($payload, string $algo = 'HS256'): string
{
    $header = ['typ' => 'JWT', 'alg' => $algo];
    $segments = [];
    $segments[] = url_safe_base64_encode(json_encode($header));
    $segments[] = url_safe_base64_encode(json_encode($payload));
    $signing_input = \implode('.', $segments);
    $signature = sign($signing_input, $algo);
    $segments[] = url_safe_base64_encode($signature);

    return \implode('.', $segments);
}

function sign(string $message, string $method = 'HS256'): string
{
    $methods = [
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512',
    ];

    if (empty($methods[$method])) {
        throw new DomainException('Algorithm not supported');
    }

    return \hash_hmac($methods[$method], $message, secret(), true);
}

/**
 * @return object|array
 */
function json_decode(string $input): object
{
    $obj = (object) \json_decode($input);

    if (
        \function_exists('json_last_error') &&
        $errno = \json_last_error()
    ) {
        handle_json_error($errno);
    } else if ($obj === null && $input !== 'null') {
        throw new DomainException('Null result with non-null input');
    }

    return $obj;
}

function json_encode($input): string
{
    $json = \json_encode($input);

    if (
        \function_exists('json_last_error') &&
        $errno = \json_last_error()
    ) {
        handle_json_error($errno);
    } else if ($json === 'null' && $input !== null) {
        throw new DomainException('Null result with non-null input');
    }

    return $json;
}

function url_safe_base64_decode(string $input): string
{
    $remainder = \strlen($input) % 4;

    if ($remainder) {
        $padlen = 4 - $remainder;
        $input .= \str_repeat('=', $padlen);
    }

    return \base64_decode(\strtr($input, '-_', '+/'));
}

function url_safe_base64_encode(string $input): string
{
    return \str_replace('=', '', \strtr(\base64_encode($input), '+/', '-_'));
}

function handle_json_error(int $errno)
{
    $messages = [
        \JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
        \JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
        \JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
    ];

    throw new DomainException(
        $messages[$errno] ?? 'Unknown JSON error: ' . $errno
    );

}
