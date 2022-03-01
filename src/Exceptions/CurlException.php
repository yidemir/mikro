<?php

namespace Mikro\Exceptions;

class CurlException extends \Exception
{
    public static ?string $response = null;
    public static ?int $status = null;
    public static array $details = [];

    public static function curlError(string $message, array $details)
    {
        self::$details = $details;

        return new self('Curl Error: ' . $message);
    }

    public static function clientError(string $response, int $status, array $details)
    {
        self::$response = $response;
        self::$status = $status;
        self::$details = $details;

        return new self('4xx Client Error');
    }

    public static function serverError(?string $response, int $status, array $details)
    {
        self::$response = $response;
        self::$status = $status;
        self::$details = $details;

        return new self('5xx Server Error');
    }

    public function isClientError(): bool
    {
        return $this->getStatus() >= 400 && $this->getStatus() < 500;
    }

    public function isServerError(): bool
    {
        return $this->getStatus() >= 500;
    }

    public function isCurlError(): bool
    {
        return ! $this->getStatus();
    }

    public function getResponse(): ?string
    {
        return self::$response;
    }

    public function getStatus(): ?int
    {
        return self::$status;
    }

    public function getDetails(): array
    {
        return self::$details;
    }
}
