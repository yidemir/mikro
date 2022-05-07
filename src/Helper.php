<?php

declare(strict_types=1);

namespace Helper
{
    use Request;
    use Mikro\Exceptions\{MikroException, CurlException};

    function optional(mixed $value): object
    {
        return new class ($value) implements \ArrayAccess {
            public function __construct(public mixed $value)
            {
                //
            }

            public function __get(string $key): mixed
            {
                if (\is_array($this->value) || $this->value instanceof \ArrayAccess) {
                    return $this->value[$key] ?? null;
                }

                if (\is_object($this->value)) {
                    return $this->value->{$key} ?? null;
                }

                return null;
            }

            public function __isset(string $key): bool
            {
                if (\is_array($this->value) || $this->value instanceof \ArrayAccess) {
                    return isset($this->value[$key]);
                }

                if (\is_object($this->value)) {
                    return isset($this->value->{$key});
                }

                return false;
            }

            public function __call(string $method, array $args): mixed
            {
                if (
                    (\is_array($this->value) || $this->value instanceof \ArrayAccess) &&
                    \is_callable($this->value[$method])
                ) {
                    return $this->value[$method](...$args);
                }

                if (\is_object($this->value)) {
                    return $this->value->{$method}(...$args);
                }

                return null;
            }

            public function offsetExists(mixed $key): bool
            {
                return (\is_array($this->value) || $this->value instanceof \ArrayAccess) && isset($this->value[$key]);
            }

            public function offsetGet(mixed $key): mixed
            {
                if (\is_array($this->value) || $this->value instanceof \ArrayAccess) {
                    return $this->value[$key] ?? null;
                }

                if (\is_object($this->value)) {
                    return $this->value->{$key} ?? null;
                }

                return null;
            }

            public function offsetSet(mixed $key, mixed $value): void
            {
                if (\is_array($this->value) || $this->value instanceof \ArrayAccess) {
                    $this->value[$key] = $value;
                }

                if (\is_object($this->value)) {
                    $this->value->{$key} = $value;
                }
            }

            public function offsetUnset(mixed $key): void
            {
                if (\is_array($this->value) || $this->value instanceof \ArrayAccess) {
                    unset($this->value[$key]);
                }

                if (\is_object($this->value)) {
                    unset($this->value->{$key});
                }
            }
        };
    }

    function csrf(): object
    {
        return new class {
            /**
             * Generate a random string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\csrf()->generateRandom();
             * ```
             */
            public static function generateRandom(int $strength): string
            {
                if (\extension_loaded('openssl')) {
                    return \hash('sha512', \openssl_random_pseudo_bytes($strength));
                }

                return \hash('sha512', \random_bytes($strength));
            }

            /**
             * Validates CSRF token
             *
             * {@inheritDoc} **Example:**
             * ```php
             * if (! Helper\csrf()->validate(Request\get('__CSRF_TOKEN'))) {
             *     throw new Exception('CSRF token does not match');
             * }
             * ```
             */
            public static function validate(?string $value = null): bool
            {
                if (! isset($_SESSION['__csrf'])) {
                    return false;
                }

                if ($value === null) {
                    $value = Request\get('__CSRF_TOKEN');
                }

                return \hash_equals($value, $_SESSION['__csrf']);
            }

            /**
             * Generate CSRF token
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $token = Helper\csrf()->get();
             * ```
             */
            public static function get(): string
            {
                if (\session_status() !== \PHP_SESSION_ACTIVE) {
                    throw new MikroException('Start the PHP Session first');
                }

                if (isset($_SESSION['__csrf'])) {
                    return $_SESSION['__csrf'];
                }

                return $_SESSION['__csrf'] = self::generateRandom(32);
            }

            /**
             * Generate CSRF input in HTML
             *
             * {@inheritDoc} **Example:**
             * ```php
             * echo Helper\csrf()->field();
             * ```
             */
            public static function field(): string
            {
                return (string) html('input', '')
                    ->name('__CSRF_TOKEN')
                    ->type('hidden')
                    ->value(self::get());
            }
        };
    }

    function flash(?string $message = null): mixed
    {
        $flash = new class {
            /**
             * Add flash message to session store
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\flash()->add('Flash message'); // add default message
             * Helper\flash()->add('Flash message', 'error'); // add error message
             * ```
             */
            public static function add(string|array $message, string $type = 'default'): string|array
            {
                if (\session_status() !== \PHP_SESSION_ACTIVE) {
                    throw new MikroException('Start the PHP Session first');
                }

                $messages = $_SESSION['__flash_' . $type] ?? [];

                if (is_array($message)) {
                    $messages = array_merge($messages, $message);
                } else {
                    $messages[] = $message;
                }

                return $_SESSION['__flash_' . $type] = $messages;
            }

            /**
             * Get flash messages on session store
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\flash()->get(); // get default messages
             * Helper\flash()->set('error'); // get error messages
             * ```
             */
            public static function get(string $type = 'default'): ?array
            {
                if (\session_status() !== \PHP_SESSION_ACTIVE) {
                    throw new MikroException('Start the PHP Session first');
                }

                $messages = $_SESSION['__flash_' . $type] ?? [];
                unset($_SESSION['__flash_' . $type]);

                return $messages;
            }
        };

        return $message ? $flash->add($message) : $flash;
    }

    /**
     * Creates a Html tag
     *
     * {@inheritDoc} **Example:**
     * ```php
     * echo Helper\html('br'); // <br>
     * echo Helper\html('table') // <table></table>
     * echo Helper\html('p', 'Hello world!'); // <p>Hello world!</p>
     * echo Helper\html('p', 'Hey!', ['class' => 'hey']); // <p class="hey">Hey!</p>
     * echo Helper\html('p', 'Hey!')->class('hey'); // <p class="hey">Hey!</p>
     * echo Helper\html('p', 'Hey!', ['PascalCase' => 'true'])->snakeCase('true')->kebabCase('false');
     * // <p PascalCase="true" snake-case="true" kebab-case="false">Hey!</p>
     *
     * echo Helper\html('div', [
     *     Helper\html('a', 'Link')->href('http://url.com')->style('text-decoration:none;'),
     *     Helper\html('a', 'Link')->href('http://url.com')->style(['text-decoration' => 'none'])
     * ]); // same result
     * ```
     *
     * New feature: Invokable tag
     * Always returns string, instead of \Stringable
     * ```php
     * Helper\html('div')->class('foo')('Content'); // <div class="foo">content</div>
     * Helper\html('span')('Text', ['class' => 'bg-light']); // <span class="bg-light">Text</span>
     * Helper\html('ul', Helper\html('li')('Item'))(); // <ul><li>Item</li></ul>
     * ```
     */
    function html(string $name, mixed $content = '', array $attributes = []): object
    {
        return new class ($name, $content, $attributes) implements \Stringable {
            public function __construct(
                protected string $name,
                protected mixed $content,
                protected array $attributes = []
            ) {
                if (\is_array($content)) {
                    $content = \implode('', \array_map(fn($tag) => (string) $tag, $content));
                }

                $this->content = (string) $content;
            }

            public function __call(string $name, array $args): self
            {
                $name = \strtolower(\preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
                $attribute = $args[0] ?? null;
                $append = $args[1] ?? null;

                $this->attributes[$name] = isset($this->attributes[$name]) && $append === true ?
                    $this->attributes[$name] . $attribute : $attribute;

                return $this;
            }

            public function __toString(): string
            {
                $attributes = '';

                foreach ($this->attributes as $key => $value) {
                    if (\is_array($value)) {
                        $value = \implode('', \array_map(function ($key, $value) {
                            return "{$key}:{$value};";
                        }, \array_keys($value), \array_values($value)));
                    }

                    if ($value === null) {
                        $attributes .= "{$key} ";
                    } elseif ($value === false) {
                        // pass
                    } else {
                        $attributes .= \sprintf('%s="%s" ', $key, $value);
                    }
                }

                $attributes = empty($attributes) ? '' : ' ' . \trim($attributes);
                $tag = \sprintf('<%s%s>', $this->name, $attributes);

                if ($this->content === "0" || $this->content) {
                    $tag .= $this->content;
                }

                $selfCloseTags = [
                    'br',
                    'hr',
                    'col',
                    'img',
                    'wbr',
                    'area',
                    'base',
                    'link',
                    'meta',
                    'embed',
                    'input',
                    'param',
                    'track',
                    'source',
                ];

                if (! \in_array($this->name, $selfCloseTags)) {
                    $tag .= \sprintf('</%s>', $this->name);
                }

                return $tag;
            }

            public function __set(string $attribute, mixed $value): void
            {
                $this->attributes[$attribute] = $value;
            }

            public function __get(string $attribute): mixed
            {
                return $this->attributes[$attribute] ?? null;
            }

            public function __isset(string $attribute): bool
            {
                return \array_key_exists($attribute, $this->attributes);
            }
        };
    }

    /**
     * Creates a Curl request
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $curl = Helper\curl('http://url.com');
     * $textResponse = (string) $curl->exec(); // string
     * $textResponse = $curl->text(); // string
     * $arrayResponse = $curl->json(); // array or null
     *
     * // Request with data
     * Helper\curl('http://foo.com')->data(['foo' => 'bar'])->json(); // send request with json body
     * Helper\curl('http://foo.com')->asForm()->data(['foo' => 'bar'])->json();  // send request as form
     * Helper\curl('http://foo.com')->asForm()->data(['foo' => 'bar'])->json();  // send request as form
     *
     * // Request with header
     * Helper\curl('http://foo.com')->headers(['Content-type' => 'application/json'])->json();
     *
     * // Request with another method
     * Helper\curl('http://foo.com', 'post');
     * Helper\curl('http://foo.com')->method('post')->json();
     *
     * // Get request details
     * $curl = Helper\curl('http://foo.com')->method('post')->data(['x' => 'y'])->exec();
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
     * Helper\curl('http://foo.com', 'PUT', [
     *     \CURLOPT_FOLLOWLOCATION => true
     * ]);
     * Helper\curl('http://foo.com', 'PUT')->followLocation(); // same as above
     *
     * Helper\curl('http://foo.com')->options([
     *     \CURLOPT_FOLLOWLOCATION => true,
     *     \CURLOPT_URL => 'http://newurl.com'
     * ])->json();
     * ```
     */
    function curl(string $url, string $method = 'GET', array $options = []): object
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
             * Helper\curl('url')->exec();
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
             * Helper\curl('url')->text();
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
             * Helper\curl('url')->json();
             * ```
             */
            public function json(): ?array
            {
                if ($this->response === null) {
                    $this->exec();
                }

                try {
                    return \json_decode($this->response, true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    return null;
                }
            }

            /**
             * Return curl info data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $curl = Helper\curl('url')->exec();
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
             * Helper\curl('url')->method('POST')->exec();
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
             * Helper\curl('url')->options(['CURLOPT_FOLLOWLOCATION' => true])->exec();
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
             * Helper\curl('url')->data(['foo' => 'bar'])->exec();
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
             * Helper\curl('url')->headers(['Authorization' => 'Bearer token'])->exec();
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
             * Helper\curl('url')->followLocation()->exec();
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
             * Helper\curl('url')->asForm()->data(['foo' => 'bar'])->exec();
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
             * Helper\curl('url')->exec()->getStatus(); // 200
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
             * Helper\curl('url')->exec()->getError();
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
             * $request = Helper\curl('url')->exec();
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
             * $request = Helper\curl('url')->exec();
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
             * $request = Helper\curl('url')->exec();
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
             * $request = Helper\curl('url')->exec();
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
             * $request = Helper\curl('url')->exec();
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
             * Helper\curl('url')->errorOnFail()->exec();
             * ```
             */
            public function errorOnFail(bool $errorOnFail = true): self
            {
                $this->errorOnFail = $errorOnFail;

                return $this;
            }
        };
    }

    /**
     * Returns pagination data based on the total number of items
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Helper\paginate(100);
     * Helper\paginate(100, 2);
     * Helper\paginate(100, Request\input('page'), 25);
     * ```
     */
    function paginate(int|string $total, int|string $page = 1, int|string $limit = 10): object
    {
        return new class (\intval($total), \intval($page), \intval($limit)) implements \IteratorAggregate, \Countable {
            public array $data;
            public array $items = [];

            public function __construct(public int $total, public int $page, public int $limit)
            {
                $this->data = [
                    'page' => $page = $page < 1 ? 1 : $page,
                    'max' => $max = \ceil($total / $limit) * $limit,
                    'limit' => $limit,
                    'offset' => ($offset = ($page - 1) * $limit) > $max ? $max : $offset,
                    'total_page' => $totalPage = \intval($max / $limit),
                    'current_page' => $currentPage = $page > $totalPage ? $totalPage : $page,
                    'next_page' => $currentPage + 1 > $totalPage ? $totalPage : $currentPage + 1,
                    'previous_page' => $currentPage - 1 ?: 1,
                ];
            }

            /**
             * Get pagination data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\paginate(100, Request\get('currentPage'))->getData();
             * ```
             */
            public function getData(): array
            {
                return $this->data;
            }

            /**
             * Get current page
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\paginate(100, Request\get('currentPage'))->getPage();
             * ```
             */
            public function getPage(): int
            {
                return $this->data['page'];
            }

            /**
             * Get pagination limit
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\paginate(100, Request\get('currentPage'))->getLimit();
             * ```
             */
            public function getLimit(): int
            {
                return $this->data['limit'];
            }

            /**
             * Get pagination offset
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\paginate(100, Request\get('currentPage'))->getOffset();
             * ```
             */
            public function getOffset(): int
            {
                return $this->data['offset'];
            }

            /**
             * Get pagination total page
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $totalPage = Helper\paginate(100, Request\get('currentPage'))->getTotalPage();
             * $pages = range(1, $totalPage);
             * ```
             */
            public function getTotalPage(): int
            {
                return $this->data['total_page'];
            }

            /**
             * Get pagination current page
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\paginate(100, Request\get('currentPage'))->getCurrentPage();
             * ```
             */
            public function getCurrentPage(): int
            {
                return $this->data['current_page'];
            }

            /**
             * Get pagination next page
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\paginate(100, Request\get('currentPage'))->getNextPage();
             * ```
             */
            public function getNextPage(): int
            {
                return $this->data['next_page'];
            }

            /**
             * Get pagination previous page
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\paginate(100, Request\get('currentPage'))->getPreviousPage();
             * ```
             */
            public function getPreviousPage(): int
            {
                return $this->data['previous_page'];
            }

            /**
             * Get pagination pages data as array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * Helper\paginate(100, Request\get('currentPage'))->getPages();
             * ```
             */
            public function getPageNumbers(): array
            {
                return \range(1, $this->getTotalPage());
            }

            /**
             * Set pagination items
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $paginator = Helper\paginate(100, Request\get('currentPage'));
             * $paginator->setItems($data);
             * ```
             */
            public function setItems(array $items): self
            {
                $this->items = $items;

                return $this;
            }

            /**
             * Get pagination items
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $paginator = Helper\paginate(100, Request\get('currentPage'));
             * $paginator->setItems($data);
             * $items = $paginator->getItems();
             * ```
             */
            public function getItems(): array
            {
                return $this->items;
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator($this->getItems());
            }

            public function count(): int
            {
                return \count($this->getItems());
            }
        };
    }
}
