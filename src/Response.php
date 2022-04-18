<?php

declare(strict_types=1);

namespace Response
{
    use View;

    /**
     * Output response with headers
     *
     * ```php
     * Response\output();
     * Response\output(null, Response\STATUS['HTTP_NOT_FOUND']);
     * Response\output(null, Response\STATUS['HTTP_MOVED_PERMANENTLY'], ['Location' => 'http://foo']);
     * ```
     */
    function output(
        ?string $content = null,
        int $code = STATUS['HTTP_OK'],
        array $headers = []
    ): int {
        foreach ($headers as $key => $value) {
            header($key, $value);
        }

        \http_response_code($code);

        return print($content);
    }

    /**
     * Output a html response
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Response\html('Hello world!');
     * Response\html('404 page not found!', Response\STATUS['HTTP_NOT_FOUND']);
     * Response\html('Error!', Response\STATUS['HTTP_INTERNAL_SERVER_ERROR']);
     * ```
     */
    function html(
        string $content,
        int $code = STATUS['HTTP_OK'],
        array $headers = []
    ): int {
        if (! \array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = 'text/html;charset=utf-8';
        }

        return output($content, $code, $headers);
    }

    /**
     * Output a json response
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Response\json(['status' => true]);
     * Response\json(['status' => false], Response\STATUS['HTTP_NOT_FOUND']);
     * Response\json(['status' => false], Response\STATUS['HTTP_INTERNAL_SERVER_ERROR']);
     * ```
     */
    function json(
        mixed $content,
        int $code = STATUS['HTTP_OK'],
        array $headers = []
    ): int {
        if (! \array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = 'application/json;charset=utf-8';
        }

        $json = (string) \json_encode($content);

        return output($json, $code, $headers);
    }

    /**
     * Output a text response
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Response\text('Hello world!');
     * Response\text('404 page not found!', Response\STATUS['HTTP_NOT_FOUND']);
     * Response\text('Error!', Response\STATUS['HTTP_INTERNAL_SERVER_ERROR']);
     * ```
     */
    function text(
        string $content,
        int $code = STATUS['HTTP_OK'],
        array $headers = []
    ): int {
        if (! \array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = 'text/plain;charset=utf-8';
        }

        return output($content, $code, $headers);
    }

    /**
     * Redirect response
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Response\redirect('url');
     * Response\redirect('url', STATUS['HTTP_PERMANENTLY_REDIRECT']);
     * ```
     */
    function redirect(string $to, int $code = STATUS['HTTP_MOVED_PERMANENTLY']): void
    {
        \http_response_code($code);

        header('Location', $to);
    }

    /**
     * Redirect response to referer url
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Response\redirect_back();
     * ```
     */
    function redirect_back(int $code = STATUS['HTTP_MOVED_PERMANENTLY']): void
    {
        \http_response_code($code);
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';

        header('Location', $referer);
    }

    /**
     * Render view and output html response
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Response\view('view_file');
     * Response\view('view_file', ['data' => 'foo']);
     * Response\view('errors/404', ['data' => 'foo'], STATUS['HTTP_NOT_FOUND']);
     * Response\view('errors/500', [], STATUS['HTTP_INTERNAL_SERVER_ERROR'], [...$headers]);
     * ```
     */
    function view(
        string $file,
        array $data = [],
        int $code = STATUS['HTTP_OK'],
        array $headers = []
    ): int {
        return html(View\render($file, $data), $code, $headers);
    }

    /**
     * Send header with key/value
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Response\header('Content-type', 'text/html');
     * Response\header('Location', 'url');
     * ```
     */
    function header(string $key, string $value, mixed ...$args): void
    {
        \header(\sprintf('%s:%s', $key, $value), ...$args);
    }

    /**
     * Output a success response (status code: 200)
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Response\ok(['response_type' => 'JSON']);
     * Response\ok('Response type: HTML');
     * ```
     */
    function ok(mixed $data, int $code = STATUS['HTTP_OK'], array $headers = [])
    {
        if (\is_array($data) || \is_object($data)) {
            return json($data, $code, $headers);
        }

        return html($data, $code, $headers);
    }

    /**
     * Output a fail response (status code: 500)
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Response\error(['response_type' => 'JSON']);
     * Response\error('Response type: HTML');
     * Response\error('error details', Response\STATUS['HTTP_NOT_FOUND']);
     * ```
     */
    function error(mixed $data, int $code = STATUS['HTTP_INTERNAL_SERVER_ERROR'], array $headers = [])
    {
        if (\is_array($data) || \is_object($data)) {
            return json($data, $code, $headers);
        }

        return html($data, $code, $headers);
    }

    /**
     * Http status codes
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Response\json($data, Response\STATUS['HTTP_OK']);
     * Response\html($data, Response\STATUS['HTTP_NOT_FOUND']);
     * Response\view('pages/error', ['error' => $e], Response\STATUS['HTTP_INTERNAL_SERVER_ERROR']);
     * ```
     */
    const STATUS = [
        'HTTP_CONTINUE' => 100,
        'HTTP_SWITCHING_PROTOCOLS' => 101,
        'HTTP_PROCESSING' => 102,
        'HTTP_EARLY_HINTS' => 103,
        'HTTP_OK' => 200,
        'HTTP_CREATED' => 201,
        'HTTP_ACCEPTED' => 202,
        'HTTP_NON_AUTHORITATIVE_INFORMATION' => 203,
        'HTTP_NO_CONTENT' => 204,
        'HTTP_RESET_CONTENT' => 205,
        'HTTP_PARTIAL_CONTENT' => 206,
        'HTTP_MULTI_STATUS' => 207,
        'HTTP_ALREADY_REPORTED' => 208,
        'HTTP_IM_USED' => 226,
        'HTTP_MULTIPLE_CHOICES' => 300,
        'HTTP_MOVED_PERMANENTLY' => 301,
        'HTTP_FOUND' => 302,
        'HTTP_SEE_OTHER' => 303,
        'HTTP_NOT_MODIFIED' => 304,
        'HTTP_USE_PROXY' => 305,
        'HTTP_RESERVED' => 306,
        'HTTP_TEMPORARY_REDIRECT' => 307,
        'HTTP_PERMANENTLY_REDIRECT' => 308,
        'HTTP_BAD_REQUEST' => 400,
        'HTTP_UNAUTHORIZED' => 401,
        'HTTP_PAYMENT_REQUIRED' => 402,
        'HTTP_FORBIDDEN' => 403,
        'HTTP_NOT_FOUND' => 404,
        'HTTP_METHOD_NOT_ALLOWED' => 405,
        'HTTP_NOT_ACCEPTABLE' => 406,
        'HTTP_PROXY_AUTHENTICATION_REQUIRED' => 407,
        'HTTP_REQUEST_TIMEOUT' => 408,
        'HTTP_CONFLICT' => 409,
        'HTTP_GONE' => 410,
        'HTTP_LENGTH_REQUIRED' => 411,
        'HTTP_PRECONDITION_FAILED' => 412,
        'HTTP_REQUEST_ENTITY_TOO_LARGE' => 413,
        'HTTP_REQUEST_URI_TOO_LONG' => 414,
        'HTTP_UNSUPPORTED_MEDIA_TYPE' => 415,
        'HTTP_REQUESTED_RANGE_NOT_SATISFIABLE' => 416,
        'HTTP_EXPECTATION_FAILED' => 417,
        'HTTP_I_AM_A_TEAPOT' => 418,
        'HTTP_MISDIRECTED_REQUEST' => 421,
        'HTTP_UNPROCESSABLE_ENTITY' => 422,
        'HTTP_LOCKED' => 423,
        'HTTP_FAILED_DEPENDENCY' => 424,
        'HTTP_TOO_EARLY' => 425,
        'HTTP_UPGRADE_REQUIRED' => 426,
        'HTTP_PRECONDITION_REQUIRED' => 428,
        'HTTP_TOO_MANY_REQUESTS' => 429,
        'HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE' => 431,
        'HTTP_UNAVAILABLE_FOR_LEGAL_REASONS' => 451,
        'HTTP_INTERNAL_SERVER_ERROR' => 500,
        'HTTP_NOT_IMPLEMENTED' => 501,
        'HTTP_BAD_GATEWAY' => 502,
        'HTTP_SERVICE_UNAVAILABLE' => 503,
        'HTTP_GATEWAY_TIMEOUT' => 504,
        'HTTP_VERSION_NOT_SUPPORTED' => 505,
        'HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL' => 506,
        'HTTP_INSUFFICIENT_STORAGE' => 507,
        'HTTP_LOOP_DETECTED' => 508,
        'HTTP_NOT_EXTENDED' => 510,
        'HTTP_NETWORK_AUTHENTICATION_REQUIRED' => 511,
    ];
};
