<?php

declare(strict_types=1);

namespace Helper
{
    function arr(array $arr)
    {
        return new class ($arr) implements \ArrayAccess, \Iterator, \Countable {
            public function __construct(public array $arr)
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

            public function except(array $keys)
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

            public function mapWithKeys(callable $callback)
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
                    $new[$key] = $this->arr[$key];
                }

                $this->arr = $new;

                return $this;
            }

            public function pluck(mixed $itemKey, mixed $keyKey = null)
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

            public function push(mixed $value)
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
        };
    }

    function str(mixed $string)
    {
        return new class ((string) $string) implements \Stringable, \Countable {
            public function __construct(public string $string)
            {
                //
            }

            public function append(string $string): self
            {
                $this->string .= $string;

                return $this;
            }

            public function basename(string $extension = ''): self
            {
                $this->string = \basename($this->string, $extension);

                return $this;
            }

            public function contains(string $string): bool
            {
                return \str_contains($this->string, $string);
            }

            public function dirname(int $level = 1): self
            {
                $this->string = \dirname($this->string, $level);

                return $this;
            }

            public function endsWith(string $string): bool
            {
                return \str_ends_with($this->string, $string);
            }

            public function explode(string $string): array
            {
                if (empty($string)) {
                    return [];
                }

                return \explode($string, $this->string);
            }

            public function finish(string $string): self
            {
                if (! \str_ends_with($this->string, $string)) {
                    $this->string = $this->string . $string;
                }

                return $this;
            }

            public function lcfirst(): self
            {
                $firstChar = \mb_substr($this->string, 0, 1);
                $this->string = \mb_strtolower($firstChar) . \mb_substr($this->string, 1, null);

                return $this;
            }

            public function length(): int
            {
                return \mb_strlen($this->string);
            }

            public function limit(int $limit, string $last = '...'): self
            {
                $this->string = \mb_substr($this->string, 0, $limit) . $last;

                return $this;
            }

            public function lower(): self
            {
                $this->string = \mb_convert_case($this->string, \MB_CASE_LOWER);

                return $this;
            }

            public function ltrim(?string $string = " \n\r\t\v\x00"): self
            {
                $this->string = \ltrim($this->string, $string);

                return $this;
            }

            public function prepend(string $string): self
            {
                $this->string = $string . $this->string;

                return $this;
            }

            public function remove(string $string): self
            {
                $this->string = \str_replace($string, '', $this->string);

                return $this;
            }

            public function replace(string $search, string $replace): self
            {
                $this->string = \str_replace($search, $replace, $this->string);

                return $this;
            }

            public function rtrim(?string $string = " \n\r\t\v\x00"): self
            {
                $this->string = \rtrim($this->string, $string);

                return $this;
            }

            public function reverse(): self
            {
                $this->string = \strrev($this->string);

                return $this;
            }

            public function start(string $string): self
            {
                if (! \str_starts_with($this->string, $string)) {
                    $this->string = $string . $this->string;
                }

                return $this;
            }

            public function startsWith(string $string): bool
            {
                return \str_starts_with($this->string, $string);
            }

            public function substr(int $start, ?int $length = null): self
            {
                $this->string = \mb_substr($this->string, $start, $length);

                return $this;
            }

            public function translate(array $values): self
            {
                $this->string = \strtr($this->string, $values);

                return $this;
            }

            public function trim(?string $string = " \n\r\t\v\x00"): self
            {
                $this->string = \trim($this->string, $string);

                return $this;
            }

            public function title(): self
            {
                $this->string = \mb_convert_case($this->string, \MB_CASE_TITLE);

                return $this;
            }

            public function ucfirst(): self
            {
                $firstChar = \mb_substr($this->string, 0, 1);
                $this->string = \mb_strtoupper($firstChar) . \mb_substr($this->string, 1, null);

                return $this;
            }

            public function upper(): self
            {
                $this->string = \mb_convert_case($this->string, \MB_CASE_UPPER);

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
                return \str_word_count($this->string);
            }

            public function get(): string
            {
                return $this->string;
            }

            public function __toString(): string
            {
                return $this->get();
            }

            public function count(): int
            {
                return $this->length();
            }
        };
    }

    function optional(mixed $value)
    {
        return new class ($value) implements \ArrayAccess {
            public function __construct(public mixed $value)
            {
                //
            }

            public function __get(string $key): mixed
            {
                if (\is_object($this->value)) {
                    return $this->value->{$key} ?? null;
                }

                if (\is_array($this->value)) {
                    return $this->value[$key] ?? null;
                }

                return null;
            }

            public function __isset(string $key): bool
            {
                if (\is_object($this->value)) {
                    return isset($this->value->{$key});
                }

                if (\is_array($this->value)) {
                    return isset($this->value[$key]);
                }

                return false;
            }

            public function __call(string $method, array $args): mixed
            {
                if (\is_object($this->value)) {
                    return $this->value->{$method}(...$args);
                }

                if (
                    (\is_array($this->value)) &&
                    \is_callable($this->value[$method])
                ) {
                    return $this->value[$method](...$args);
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

                return null;
            }

            public function offsetSet(mixed $key, mixed $value): void
            {
                if (\is_array($this->value) || $this->value instanceof \ArrayAccess) {
                    $this->value[$key] = $value;
                }
            }

            public function offsetUnset(mixed $key): void
            {
                if (\is_array($this->value) || $this->value instanceof \ArrayAccess) {
                    unset($this->value[$key]);
                }
            }
        };
    }
};
