<?php

declare(strict_types=1);

namespace Curl
{
    use Mikro\Exceptions\CurlException;

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
            /**
             * Curl resource
             *
             * @var mixed
             */
            public mixed $curl;

            /**
             * Send request as form
             *
             * @var boolean
             */
            public bool $asForm = false;

            /**
             * Throw exception on fail
             *
             * @var boolean
             */
            public bool $errorOnFail = false;

            /**
             * Response data
             *
             * @var null|string
             */
            public ?string $response = null;

            public function __construct(
                public string $url,
                public string $method,
                public array $options
            ) {
                $this->method($method);
                $this->options($options);
                $this->curl = \curl_init();
            }

            /**
             * Execute actual request
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->exec();
             * ```
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

                if ($this->errorOnFail) {
                    if (! empty($this->getError())) {
                        throw CurlException::curlError($this->getError(), [
                            'method' => $this->method,
                            'options' => $options + $this->options,
                            'info' => $this->getInfo(),
                        ]);
                    }

                    if ($this->isClientError()) {
                        throw CurlException::clientError($this->text(), $this->getStatus(), [
                            'method' => $this->method,
                            'options' => $options + $this->options,
                            'info' => $this->getInfo(),
                        ]);
                    }

                    if ($this->isServerError()) {
                        throw CurlException::clientError($this->text(), $this->getStatus(), [
                            'method' => $this->method,
                            'options' => $options + $this->options,
                            'info' => $this->getInfo(),
                        ]);
                    }
                }

                return $this;
            }

            public function __toString(): string
            {
                return $this->response ?? '';
            }

            /**
             * Return response as text
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->text();
             * ```
             */
            public function text(): string
            {
                if ($this->response === null) {
                    $this->exec();
                }

                return (string) $this->response;
            }

            /**
             * Return response as array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->json();
             * ```
             */
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

            /**
             * Return curl info data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $curl = Curl\make('url')->exec();
             * $status = $curl->getInfo('status_code');
             * ```
             */
            public function getInfo(?string $key = null): mixed
            {
                if (\curl_errno($this->curl)) {
                    return null;
                }

                $info = \curl_getinfo($this->curl);

                if ($key === null) {
                    return $info;
                }

                return $info[$key] ?? null;
            }

            /**
             * Set request method
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->method('POST')->exec();
             * ```
             */
            public function method(string $method): self
            {
                $this->method = \strtoupper($method);

                return $this;
            }

            /**
             * Set curl options
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->options(['CURLOPT_FOLLOWLOCATION' => true])->exec();
             * ```
             */
            public function options(array $options): self
            {
                $this->options = $options;

                return $this;
            }

            /**
             * Set request form/body data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->data(['foo' => 'bar'])->exec();
             * ```
             */
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

            /**
             * Set request headers
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->headers(['Authorization' => 'Bearer token'])->exec();
             * ```
             */
            public function headers(array $headers): self
            {
                $this->options[\CURLOPT_HTTPHEADER] = \array_map(
                    fn($key, $val) => "{$key}: {$val}",
                    \array_keys($headers),
                    $headers
                );

                return $this;
            }

            /**
             * Set curl 'follow location' parameter
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->followLocation()->exec();
             * ```
             */
            public function followLocation(): self
            {
                $this->options[\CURLOPT_FOLLOWLOCATION] = true;

                return $this;
            }

            /**
             * Send request as form
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->asForm()->data(['foo' => 'bar'])->exec();
             * ```
             */
            public function asForm(): self
            {
                $this->asForm = true;

                return $this;
            }

            /**
             * Get actual response status
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->exec()->getStatus(); // 200
             * ```
             */
            public function getStatus(): ?int
            {
                return $this->getInfo('http_code');
            }

            /**
             * Get curl error
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->exec()->getError();
             * ```
             */
            public function getError(): string
            {
                return \curl_error($this->curl);
            }

            /**
             * Check is request successful
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $request = Curl\make('url')->exec();
             *
             * $request->isOk(); // bool
             * ```
             */
            public function isOk(): bool
            {
                return $this->getStatus() >= 200 && $this->getStatus() < 300;
            }

            /**
             * Check is request redirected
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $request = Curl\make('url')->exec();
             *
             * $request->isRedirect();
             * ```
             */
            public function isRedirect(): bool
            {
                return $this->getStatus() >= 300 && $this->getStatus() < 400;
            }

            /**
             * Check is request failed
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $request = Curl\make('url')->exec();
             *
             * $request->isFailed();
             * ```
             */
            public function isFailed(): bool
            {
                return $this->isServerError() || $this->isClientError();
            }

            /**
             * Check is server responded 5xx request
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $request = Curl\make('url')->exec();
             *
             * $request->isServerError();
             * ```
             */
            public function isServerError(): bool
            {
                return $this->getStatus() >= 500;
            }

            /**
             * Check is server responded 4xx request
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $request = Curl\make('url')->exec();
             *
             * $request->isClientError();
             * ```
             */
            public function isClientError(): bool
            {
                return $this->getStatus() >= 400 && $this->getStatus() < 500;
            }

            /**
             * Set request is failed, throw an exception
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Curl\make('url')->errorOnFail()->exec();
             * ```
             */
            public function errorOnFail(bool $errorOnFail = true): self
            {
                $this->errorOnFail = $errorOnFail;

                return $this;
            }
        };
    }
};
