<?php

declare(strict_types=1);

namespace Helper
{
    use Pagination;
    use Mikro\Exceptions\ValidatorException;

    function arr(array $arr = []): object
    {
        return new class ($arr) implements \ArrayAccess, \Iterator, \Countable {
            protected ?object $pagination = null;
            protected static array $methods = [];

            public function __construct(public array $arr = [])
            {
                //
            }

            /**
             * Create new arr object
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr()->when(true, fn() => $this->make(['new arr']));
             * ```
             */
            public static function make(array $arr)
            {
                return new self($arr);
            }

            /**
             * Convert arr object to array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])->all(); // [1, 2, 3]
             * ```
             */
            public function all(): array
            {
                return $this->toArray();
            }

            /**
             * Chunk array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3, 4, 5, 6])->chunk(2);
             * // [[1, 2], [3, 4], [5, 6]]
             * ```
             */
            public function chunk(int $size, bool $preverseKeys = false): self
            {
                $this->arr = \array_chunk($this->arr, $size, $preverseKeys);

                return $this;
            }

            /**
             * Determine if a array contains a given item
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])->contains(2); // true
             * arr([1, 2, 3])->contains(4); // false
             * arr([1, 2, 3])->contains('2'); // true
             * arr([1, 2, 3])->contains('2', true); // false
             * ```
             */
            public function contains(mixed $value, bool $strict = false): bool
            {
                return \in_array($value, $this->arr, $strict);
            }

            /**
             * Count array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])->count(); // 3
             * ```
             */
            public function count(): int
            {
                return \count($this->arr);
            }

            /**
             * Apply callback each array item
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])->each(fn($item) => things($item));
             * ```
             */
            public function each(callable $callback): self
            {
                foreach ($this->arr as $key => $value) {
                    $callback($value, $key);
                }

                return $this;
            }

            /**
             * Removes the specified keys from array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['key' => 1, 'val' => 2])->except(['key'])->all();
             * // ['val' => 2]
             * ```
             */
            public function except(array $keys): self
            {
                $this->arr = \array_diff_key($this->arr, \array_flip($keys));

                return $this;
            }

            /**
             * Filter array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3, 4])->except(fn($i) => $i > 2)->all();
             * // [3, 4]
             * ```
             */
            public function filter(callable $callback, int $mode = 0): self
            {
                $this->arr = \array_filter($this->arr, $callback, $mode);

                return $this;
            }

            /**
             * Gets first array item
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3, 4])->first();
             * // 1
             * ```
             */
            public function first(): mixed
            {
                return $this->arr[array_key_first($this->arr)];
            }

            /**
             * Flip array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2])->flip()->all();
             * // [1 => 0, 2 => 1]
             * ```
             */
            public function flip(): self
            {
                $this->arr = \array_flip($this->arr);

                return $this;
            }

            /**
             * Delete array item
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3, 4])->forget(1);
             * // [2, 3, 4]
             * ```
             */
            public function forget(mixed $key): self
            {
                unset($this->arr[$key]);

                return $this;
            }

            /**
             * Paginate array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3, 4, 5, 6])->forPage(2, 2)->all();
             * // [3, 4]
             * ```
             */
            public function forPage(int $page, int $length = 10): array
            {
                $this->chunk($length);

                return $this->arr[$page - 1] ?? [];
            }

            /**
             * Get array item
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $arr = arr(['name' => 'value', ['key' => ['name' => 'foo']]]);
             * $arr->get('name'); // value
             * $arr->get('key.name'); // foo
             * ```
             */
            public function get(mixed $key, mixed $default = null): mixed
            {
                if (\array_key_exists($key, $this->arr)) {
                    return $this->arr[$key];
                }

                return \array_reduce(
                    \explode('.', $key),
                    fn($array, $key) => $array[$key] ?? $default,
                    $this->arr
                ) ?? $default;
            }

            /**
             * Group by array item
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([
             *     ['name' => 'foo', 'cid' => 1],
             *     ['name' => 'bar', 'cid' => 1],
             *     ['name' => 'baz', 'cid' => 2],
             * ])->groupBy('cid')->all();
             * // [
             * //     1 => [
             * //         ['name' => 'foo', 'cid' => 1],
             * //         ['name' => 'bar', 'cid' => 1]
             * //     ],
             * //     2 => [
             * //         ['name' => 'baz', 'cid' => 2]
             * //     ]
             * // ]
             * ```
             */
            public function groupBy(mixed $key): self
            {
                $new = [];

                foreach ($this->arr as $item) {
                    $new[$item[$key]][] = $item;
                }

                $this->arr = $new;

                return $this;
            }

            /**
             * Check item exists
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $arr = arr([1 => [], 2 => [], 3 => [], 4 => []]);
             * $arr->has(1); // true
             * $arr->has(7); // false
             * $arr->has('key'); // false
             * ```
             */
            public function has(mixed $key): bool
            {
                return isset($this->arr[$key]);
            }

            /**
             * Checks array is empty
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3, 4])->isEmpty(); // false
             * arr([])->isEmpty(); // true
             * arr()->isEmpty(); // true
             * ```
             */
            public function isEmpty(): bool
            {
                return empty($this->arr);
            }

            public function implode(string $seperator): string
            {
                return \implode($seperator, $this->arr);
            }

            public function isNotEmpty(): bool
            {
                return ! $this->isEmpty();
            }

            public function keys(): self
            {
                $this->arr = \array_keys($this->arr);

                return $this;
            }

            public function last(): mixed
            {
                return $this->arr[array_key_last($this->arr)];
            }

            public function map(callable $callback): self
            {
                return new self(\array_map($callback, $this->arr, \array_keys($this->arr)));
            }

            public function mapWithKeys(callable $callback): self
            {
                $new = [];
                $array = \array_map($callback, $this->arr, \array_keys($this->arr));

                foreach ($array as $data) {
                    $new += $data;
                }

                return new self($new);
            }

            public function merge(array $arr): self
            {
                $this->arr = \array_merge($this->arr, $arr);

                return $this;
            }

            public function only(array $keys): self
            {
                $new = [];

                foreach ($keys as $key) {
                    if (\array_key_exists($key, $this->arr)) {
                        $new[$key] = $this->arr[$key];
                    }
                }

                $this->arr = $new;

                return $this;
            }

            public function parseJson(string $json): self
            {
                $this->arr = (array) \json_decode($json);

                return $this;
            }

            public function pluck(mixed $itemKey, mixed $keyKey = null): self
            {
                $new = [];

                foreach ($this->arr as $key => $item) {
                    if ($keyKey === null) {
                        $new[] = $item[$itemKey];
                    } else {
                        $new[$item[$keyKey]] = $item[$itemKey];
                    }
                }

                $this->arr = $new;

                return $this;
            }

            public function pop(): mixed
            {
                return \array_pop($this->arr);
            }

            public function pull(mixed $key): mixed
            {
                $item = $this->arr[$key];
                unset($this->arr[$key]);

                return $item;
            }

            public function push(mixed $value): self
            {
                $this->arr[] = $value;

                return $this;
            }

            public function put(mixed $key, mixed $item): self
            {
                if (! \str_contains($key, '.')) {
                    $this->arr[$key] = $item;

                    return $this;
                }

                $this->arr = \array_replace_recursive($this->arr, \array_reduce(
                    \array_reverse(\explode('.', $key)),
                    fn($value, $key) => [$key => $value],
                    $item
                ));

                return $this;
            }

            public function replace(array $arr): self
            {
                $this->arr = \array_replace($this->arr, $arr);

                return $this;
            }

            public function reverse(): self
            {
                $this->arr = \array_reverse($this->arr);

                return $this;
            }

            public function search(mixed $value, bool $strict = false): int|string|bool
            {
                return \array_search($value, $this->arr, $strict);
            }

            public function shift(): mixed
            {
                return \array_shift($this->arr);
            }

            public function sort(int $flags = SORT_REGULAR): self
            {
                \sort($this->arr, $flags);

                return $this;
            }

            public function toArray(): array
            {
                return $this->arr;
            }

            public function toJson(int $flags = 0, int $depth = 512): string|false
            {
                return \json_encode($this->arr, $flags, $depth);
            }

            public function transform(callable $callback): self
            {
                foreach ($this->arr as $key => $item) {
                    $this->arr[$key] = $callback($item, $key);
                }

                return $this;
            }

            public function unless(bool $boolean, callable $callback): self
            {
                return $this->when(! $boolean, $callback);
            }

            public function values(): self
            {
                $this->arr = \array_values($this->arr);

                return $this;
            }

            public function when(bool $boolean, callable $callback): self
            {
                if ($boolean) {
                    $callback($this);
                }

                return $this;
            }

            public function offsetExists(mixed $offset): bool
            {
                return isset($this->arr[$offset]);
            }

            public function offsetGet(mixed $offset): mixed
            {
                return $this->arr[$offset];
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
                if ($offset === null) {
                    $this->arr[] = $value;
                } else {
                    $this->arr[$offset] = $value;
                }
            }

            public function offsetUnset(mixed $offset): void
            {
                unset($this->arr[$offset]);
            }

            public function current(): mixed
            {
                return \current($this->arr);
            }

            public function key(): mixed
            {
                return \key($this->arr);
            }

            public function next(): void
            {
                \next($this->arr);
            }

            public function rewind(): void
            {
                \reset($this->arr);
            }

            public function valid(): bool
            {
                return \key($this->arr) !== null;
            }

            public function __get(string $key): mixed
            {
                return $this->arr[$key];
            }

            public function __set(string $key, mixed $value): void
            {
                $this->arr[$key] = $value;
            }

            public function __isset(string $key): bool
            {
                return isset($this->arr[$key]);
            }

            public function __unset(string $key): void
            {
                unset($this->arr[$key]);
            }

            public function __invoke(?callable $callback = null): mixed
            {
                if ($callback) {
                    return $callback($this);
                }

                return $this->toArray();
            }

            public function __call(string $method, array $args): mixed
            {
                if (isset(self::$methods[$method])) {
                    return self::$methods[$method]->call($this, ...$args);
                }

                throw new \Error("Call to undefined method {$method}()");
            }

            public static function register(string $method, \Closure $closure): void
            {
                self::$methods[$method] = $closure;
            }

            public function setPagination(array $data): self
            {
                $required = [
                    'page', 'max', 'limit', 'offset', 'total_page',
                    'current_page', 'next_page', 'previous_page'
                ];

                foreach ($required as $field) {
                    if (! isset($data[$field])) {
                        throw new ValidatorException(
                            "'{$field}' parameter is required in pagianation data"
                        );
                    }
                }

                $this->pagination = new class ($data) {
                    public function __construct(public array $data)
                    {
                        //
                    }

                    public function getData(?string $key = null): mixed
                    {
                        return $key ? ($this->data[$key] ?? null) : $this->data;
                    }

                    public function getPages(): array
                    {
                        return \range(1, $this->getData('total_page'));
                    }

                    public function getLinks(array $options = []): string
                    {
                        return Pagination\links($this->getData(), $options);
                    }
                };

                return $this;
            }

            public function getPagination(): object
            {
                return $this->pagination;
            }
        };
    }

    function str(mixed $str = ''): object
    {
        return new class ((string) $str) implements \Stringable, \Countable {
            protected static array $methods = [];

            public function __construct(public string $str)
            {
                //
            }

            public static function make(string $str)
            {
                return new self($str);
            }

            public function append(string $str): self
            {
                $this->str .= $str;

                return $this;
            }

            public function basename(string $extension = ''): self
            {
                $this->str = \basename($this->str, $extension);

                return $this;
            }

            public function contains(string $str): bool
            {
                return \str_contains($this->str, $str);
            }

            public function dirname(int $level = 1): self
            {
                $this->str = \dirname($this->str, $level);

                return $this;
            }

            public function endsWith(string $str): bool
            {
                return \str_ends_with($this->str, $str);
            }

            public function explode(string $str, int $limit = \PHP_INT_MAX): array
            {
                if (empty($str)) {
                    return [];
                }

                return \explode($str, $this->str, $limit);
            }

            public function finish(string $str): self
            {
                if (! \str_ends_with($this->str, $str)) {
                    $this->str = $this->str . $str;
                }

                return $this;
            }

            public function lcfirst(): self
            {
                $firstChar = \mb_substr($this->str, 0, 1);
                $this->str = \mb_strtolower($firstChar) . \mb_substr($this->str, 1, null);

                return $this;
            }

            public function length(): int
            {
                return \mb_strlen($this->str);
            }

            public function limit(int $limit, string $last = '...'): self
            {
                $this->str = \mb_substr($this->str, 0, $limit) . $last;

                return $this;
            }

            public function lower(): self
            {
                $this->str = \mb_convert_case($this->str, \MB_CASE_LOWER);

                return $this;
            }

            public function ltrim(?string $trim = " \n\r\t\v\x00"): self
            {
                $this->str = \ltrim($this->str, $trim);

                return $this;
            }

            public function prepend(string $str): self
            {
                $this->str = $str . $this->str;

                return $this;
            }

            public function remove(string $str): self
            {
                $this->str = \str_replace($str, '', $this->str);

                return $this;
            }

            public function replace(string $search, string $replace): self
            {
                $this->str = \str_replace($search, $replace, $this->str);

                return $this;
            }

            public function rtrim(?string $trim = " \n\r\t\v\x00"): self
            {
                $this->str = \rtrim($this->str, $trim);

                return $this;
            }

            public function reverse(): self
            {
                $this->str = \strrev($this->str);

                return $this;
            }

            public function start(string $str): self
            {
                if (! \str_starts_with($this->str, $str)) {
                    $this->str = $str . $this->str;
                }

                return $this;
            }

            public function startsWith(string $str): bool
            {
                return \str_starts_with($this->str, $str);
            }

            public function substr(int $start, ?int $length = null): self
            {
                $this->str = \mb_substr($this->str, $start, $length);

                return $this;
            }

            public function translate(array $values): self
            {
                $this->str = \strtr($this->str, $values);

                return $this;
            }

            public function trim(?string $trim = " \n\r\t\v\x00"): self
            {
                $this->str = \trim($this->str, $trim);

                return $this;
            }

            public function title(): self
            {
                $this->str = \mb_convert_case($this->str, \MB_CASE_TITLE);

                return $this;
            }

            public function ucfirst(): self
            {
                $firstChar = \mb_substr($this->str, 0, 1);
                $this->str = \mb_strtoupper($firstChar) . \mb_substr($this->str, 1, null);

                return $this;
            }

            public function upper(): self
            {
                $this->str = \mb_convert_case($this->str, \MB_CASE_UPPER);

                return $this;
            }

            public function when(bool $when, callable $callback): self
            {
                if ($when) {
                    $callback($this);
                }

                return $this;
            }

            public function wordCount(): int
            {
                return \str_word_count($this->str);
            }

            public function wrap(string $start, ?string $end = null): self
            {
                if ($end === null) {
                    $end = $start;
                }

                $this->str = $start . $this->str . $end;

                return $this;
            }

            public function get(): string
            {
                return $this->str;
            }

            public function count(): int
            {
                return $this->length();
            }

            public function __toString(): string
            {
                return $this->str;
            }

            public function __invoke(?callable $callback = null): mixed
            {
                if ($callback) {
                    return $callback($this);
                }

                return $this->__toString();
            }

            public function __call(string $method, array $args): mixed
            {
                if (isset(self::$methods[$method])) {
                    return self::$methods[$method]->call($this, ...$args);
                }

                throw new \Error("Call to undefined method {$method}()");
            }

            public static function register(string $method, \Closure $closure): void
            {
                self::$methods[$method] = $closure;
            }
        };
    }

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
};
