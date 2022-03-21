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

            public function __construct(public array $arr = [])
            {
                //
            }

            public function all(): array
            {
                return $this->toArray();
            }

            public function chunk(int $size, bool $preverseKeys = false): self
            {
                $this->arr = \array_chunk($this->arr, $size, $preverseKeys);

                return $this;
            }

            public function contains(mixed $value, bool $strict = false): bool
            {
                return \in_array($value, $this->arr, $strict);
            }

            public function count(): int
            {
                return \count($this->arr);
            }

            public function each(callable $callback): self
            {
                foreach ($this->arr as $key => $value) {
                    $callback($value, $key);
                }

                return $this;
            }

            public function except(array $keys): self
            {
                foreach ($keys as $key) {
                    unset($this->arr[$key]);
                }

                return $this;
            }

            public function filter(callable $callback, int $mode = 0): self
            {
                $this->arr = \array_filter($this->arr, $callback, $mode);

                return $this;
            }

            public function first(): mixed
            {
                return $this->arr[array_key_first($this->arr)];
            }

            public function flip(): self
            {
                $this->arr = \array_flip($this->arr);

                return $this;
            }

            public function forget(mixed $key): self
            {
                unset($this->arr[$key]);

                return $this;
            }

            public function forPage(int $page, int $length = 10): array
            {
                $this->chunk($length);

                return $this->arr[$page - 1] ?? [];
            }

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

            public function groupBy(mixed $key): self
            {
                $new = [];

                foreach ($this->arr as $item) {
                    $new[$item[$key]][] = $item;
                }

                $this->arr = $new;

                return $this;
            }

            public function has(mixed $key): bool
            {
                return isset($this->arr[$key]);
            }

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

            public function setPagination(array $data): self
            {
                $required = [
                    'page', 'max', 'limit', 'offset', 'total_page', 'current_page', 'next_page', 'previous_page'
                ];

                foreach ($required as $field) {
                    if (! isset($data[$field])) {
                        throw new ValidatorException("'{$field}' parameter is required in pagianation data");
                    }
                }

                $this->pagination = new class ($data) {
                    public function __construct(public array $data)
                    {
                        //
                    }

                    public function getData(): array
                    {
                        return $this->data;
                    }

                    public function getPages(): array
                    {
                        return \range(1, $this->getTotalPage());
                    }

                    public function getLinks(array $options = []): string
                    {
                        return Pagination\links($this->getData(), $options);
                    }

                    public function getLimit(): int
                    {
                        return $this->data['limit'];
                    }

                    public function getOffset(): int
                    {
                        return $this->data['offset'];
                    }

                    public function getTotalPage(): int
                    {
                        return $this->data['total_page'];
                    }

                    public function getCurrentPage(): int
                    {
                        return $this->data['current_page'];
                    }

                    public function getNextPage(): int
                    {
                        return $this->data['next_page'];
                    }

                    public function getPreviousPage(): int
                    {
                        return $this->data['previous_page'];
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
            public function __construct(public string $str)
            {
                //
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
