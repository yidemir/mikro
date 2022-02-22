<?php

declare(strict_types=1);

namespace Curl
{
    /**
     * Creates a Curl request
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $curl = Curl\make('http://url.com');
     * $textResponse = (string) $curl->exec(); // string
     * $textResponse = $curl->text(); // string
     * $arrayResponse = $curl->json(); // array or null
     *
     * // Request with data
     * Curl\make('http://foo.com')->data(['foo' => 'bar'])->json(); // send request with json body
     * Curl\make('http://foo.com')->asForm()->data(['foo' => 'bar'])->json();  // send request as form
     * Curl\make('http://foo.com')->asForm()->data(['foo' => 'bar'])->json();  // send request as form
     *
     * // Request with header
     * Curl\make('http://foo.com')->headers(['Content-type' => 'application/json'])->json();
     *
     * // Request with another method
     * Curl\make('http://foo.com', 'post');
     * Curl\make('http://foo.com')->method('post')->json();
     *
     * // Get request details
     * $curl = Curl\make('http://foo.com')->method('post')->data(['x' => 'y'])->exec();
     *
     * $curl->getInfo(); // info array
     * https://www.php.net/manual/function.curl-getinfo.php
     * $curl->getInfo('http_code'); // 200
     * $curl->getInfo('url'); // http://foo.com
     * $curl->getInfo('total_time');
     * $curl->getInfo('connect_time');
     * $curl->getInfo('redirect_url');
     * $curl->getInfo('request_header');
     * ...
     *
     * // Request with Curl options
     * Curl\make('http://foo.com', 'PUT', [
     *     \CURLOPT_FOLLOWLOCATION => true
     * ]);
     * Curl\make('http://foo.com', 'PUT')->followLocation(); // same as above
     *
     * Curl\make('http://foo.com')->options([
     *     \CURLOPT_FOLLOWLOCATION => true,
     *     \CURLOPT_URL => 'http://newurl.com'
     * ])->json();
     * ```
     */
    function make(string $url, string $method = 'GET', array $options = []): object
    {
        return new class ($url, $method, $options) {
            private string $method;
            private string $url;
            private array $options;
            private ?string $response = null;
            private bool $asForm = false;
            private mixed $curl;

            public function __construct(string $url, string $method, array $options)
            {
                $this->url = $url;
                $this->method($method);
                $this->options($options);
                $this->curl = \curl_init();
            }

            /**
             * @return $this
             */
            public function exec(): self
            {
                $options = [
                    \CURLOPT_URL => $this->url,
                    \CURLOPT_RETURNTRANSFER => true
                ];

                if ($this->method !== 'GET' && ! isset($this->options[\CURLOPT_POSTFIELDS])) {
                    $options[\CURLOPT_POSTFIELDS] = '';
                }

                if (\in_array($this->method, ['HEAD', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'])) {
                    $options[\CURLOPT_CUSTOMREQUEST] = $this->method;
                }

                \curl_setopt_array($this->curl, $options + $this->options);

                $this->response = (string) \curl_exec($this->curl);

                return $this;
            }

            public function __toString(): string
            {
                return $this->response ?? '';
            }

            public function text(): string
            {
                if ($this->response === null) {
                    $this->exec();
                }

                return (string) $this->response;
            }

            public function json(): ?array
            {
                if ($this->response === null) {
                    $this->exec();
                }

                try {
                    return \json_decode($this->response, true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    return null;
                }
            }

            public function getInfo(?string $key = null): mixed
            {
                if (\curl_errno($this->curl)) {
                    return [];
                }

                $info = \curl_getinfo($this->curl);

                if ($key === null) {
                    return $info;
                }

                return $info[$key] ?? null;
            }

            /**
             * @return $this
             */
            public function method(string $method): self
            {
                $this->method = \strtoupper($method);

                return $this;
            }

            public function options(array $options): self
            {
                $this->options = $options;

                return $this;
            }

            public function data(array $data): self
            {
                $data = $this->asForm ? \http_build_query($data) : \json_encode($data);

                switch ($this->method) {
                    case 'GET':
                        $this->url .= '?' . $data;
                        break;

                    default:
                        $this->options[\CURLOPT_POSTFIELDS] = $data;
                        break;
                }

                return $this;
            }

            public function headers(array $headers): self
            {
                $this->options[\CURLOPT_HTTPHEADER] = \array_map(
                    fn($key, $val) => "{$key}: {$val}",
                    \array_keys($headers),
                    $headers
                );

                return $this;
            }

            public function followLocation(): self
            {
                $this->options[\CURLOPT_FOLLOWLOCATION] = true;

                return $this;
            }

            public function asForm(): self
            {
                $this->asForm = true;

                return $this;
            }

            public function getError(): string
            {
                return \curl_error($this->curl);
            }

            public function isOk(): bool
            {
                return $this->getInfo('http_code') >= 200 &&
                    $this->getInfo('http_code') < 300;
            }

            public function isRedirect(): bool
            {
                return $this->getInfo('http_code') >= 300 &&
                    $this->getInfo('http_code') < 400;
            }

            public function isFailed()
            {
                return $this->isServerError() || $this->isClientError();
            }

            public function isServerError(): bool
            {
                return $this->getInfo('http_code') >= 500;
            }

            public function isClientError(): bool
            {
                return $this->getInfo('http_code') >= 400 &&
                    $this->getInfo('http_code') < 500;
            }
        };
    }
};
