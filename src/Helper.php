<?php

declare(strict_types=1);

namespace Helper
{
    use Html;
    use Request;
    use Pagination;
    use Mikro\Exceptions\{ValidatorException, MikroException, CurlException};

    /**
     * The arr method is a wrapper for arrays.
     *
     * {@inheritDoc} **Examples:**
     * ```php
     * $numbers = [1, 2, 3, 4, 5];
     * Helper\arr($numbers)->first(); // 1
     * Helper\arr($numbers)->contains(5); // true
     * Helper\arr($numbers)->map(fn($number) => $number * 5)->all(); // [5, 10, 15, 20, 25]
     * Helper\arr($numbers)->push(6)->push(7)->get(); // [1, 2, 3, 4, 5, 6, 7]
     *
     * // much more on documentation
     * ```
     */
    function arr(array $arr = []): object
    {
        return new class ($arr) implements \ArrayAccess, \Iterator, \Countable {
            /**
             * Pagination component object
             *
             * @var null|object
             */
            protected ?object $pagination = null;

            /**
             * Registered methods
             *
             * @var array<string, \Closure>
             */
            protected static array $methods = [];

            /**
             * Constructor
             *
             * @param array<mixed, mixed> $arr
             */
            public function __construct(public array $arr = [])
            {
                //
            }

            /**
             * Create new arr object
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr()->make(['new array']);
             * arr()->when(true, fn() => $this->make(['new arr']));
             * ```
             */
            public static function make(array $arr): self
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
             * Dump array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])->count(); // 3
             * ```
             */
            public function dump(): void
            {
                \var_dump($this->arr);

                return;
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
                return $this->arr[\array_key_first($this->arr)];
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

            /**
             * Join array elements with a string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3, 4])->implode('|'); // 1|2|3|4
             * ```
             */
            public function implode(string $seperator): string
            {
                return \implode($seperator, $this->arr);
            }

            /**
             * Check array is not empty
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3, 4])->isNotEmpty(); // true
             * ```
             */
            public function isNotEmpty(): bool
            {
                return ! $this->isEmpty();
            }

            /**
             * Set array keys
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['a' => 1, 'b' => 2])->keys()->all(); // ['a', 'b']
             * ```
             */
            public function keys(): self
            {
                $this->arr = \array_keys($this->arr);

                return $this;
            }

            /**
             * Get array last item
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3, 4])->last(); // 4
             * ```
             */
            public function last(): mixed
            {
                return $this->arr[\array_key_last($this->arr)];
            }

            /**
             * Map array items and creates new array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['a' => 1, 'b' => 2])->map(fn($item) => $item * 3)->all(); // [3, 6]
             * arr(['a' => 1, 'b' => 2])->map(fn($item, $key) => $key . $item * 3)->all(); // ['a3', 'b6']
             * ```
             */
            public function map(callable $callback): self
            {
                return new self(
                    \array_map($callback, $this->arr, \array_keys($this->arr))
                );
            }

            /**
             * Map array items and creates new array with keys
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['a' => 1, 'b' => 2])->map(fn($item) => [$item => $item * 3])->all(); // [1 => 3, 2 => 6]
             * arr(['a' => 1, 'b' => 2])->map(fn($item, $key) => [$key => $item * 3])->all(); // ['a' => 3, 'b' => 6]
             * ```
             */
            public function mapWithKeys(callable $callback): self
            {
                return new self(\array_reduce(
                    \array_map($callback, $this->arr, \array_keys($this->arr)),
                    fn(array $carry, array $item) => $carry + $item,
                    []
                ));
            }

            /**
             * Merge arrays
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['a' => 1, 'b' => 2])->merge(['c' => 3])->all(); // ['a' => 1, 'b' => 2, 'c' => 3]
             * ```
             */
            public function merge(array $arr): self
            {
                $this->arr = \array_merge($this->arr, $arr);

                return $this;
            }

            /**
             * Get the specified keys from array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['key' => 1, 'val' => 2])->only(['key'])->all();
             * // ['key' => 1]
             * ```
             */
            public function only(array $keys): self
            {
                $this->arr = \array_intersect_key($this->arr, \array_flip($keys));

                return $this;
            }

            /**
             * Parse json string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr()->parseJson('{"name":"value"}')->all(); // ['name' => 'value]
             * ```
             */
            public function parseJson(string $json): self
            {
                $this->arr = (array) \json_decode($json);

                return $this;
            }

            /**
             * Pluck array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $arr = arr([['data' => 'data1', 'id' => 15], ['data' => 'data2', 'id' => 23]]);
             * $arr->pluck('data')->all(); // ['data1', 'data2']
             * // or
             * $arr->pluck('data', 'id')->all(); // [15 => 'data1, 23 => 'data2']
             * ```
             */
            public function pluck(mixed $item, mixed $key = null): self
            {
                $this->arr = \array_column($this->arr, $item, $key);

                return $this;
            }

            /**
             * Same as `pluck`
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $arr = arr([['data' => 'data1', 'id' => 15], ['data' => 'data2', 'id' => 23]]);
             * $arr->column('data')->all(); // ['data1', 'data2']
             * // or
             * $arr->column('data', 'id')->all(); // [15 => 'data1, 23 => 'data2']
             * ```
             */
            public function column(mixed $item, mixed $key = null): self
            {
                return $this->pluck($item, $key);
            }

            /**
             * Pop the element off the end of array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])->pop(); // 3
             * ```
             */
            public function pop(): mixed
            {
                return \array_pop($this->arr);
            }

            /**
             * Pop the element off the end of array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['key' => 'value', 'name' => 'foo'])->pull('key'); // value
             * ```
             */
            public function pull(mixed $key, mixed $default = null): mixed
            {
                $item = $this->arr[$key] ?? $default;
                unset($this->arr[$key]);

                return $item;
            }

            /**
             * Push element to array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr()->push('foo'); // ['foo']
             * ```
             */
            public function push(mixed $value): self
            {
                $this->arr[] = $value;

                return $this;
            }

            /**
             * Put element to array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr()->put('foo', 'bar'); // ['foo' => 'bar']
             * arr()->put('foo.bar', 'baz'); ['foo' => ['bar' => 'baz']]
             * ```
             */
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

            /**
             * Replace array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['a' => 1, 'b' => 2])->replace(['a' => 2, 'c' => 3]);
             * // ['a' => 2, 'b' => 2, 'c' => 3]
             * ```
             */
            public function replace(array $arr): self
            {
                $this->arr = \array_replace($this->arr, $arr);

                return $this;
            }

            /**
             * Reverse array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['a' => 1, 'b' => 2])->reverse()->all();
             * // [1 => 'a', 2 => 'b']
             * ```
             */
            public function reverse(): self
            {
                $this->arr = \array_reverse($this->arr);

                return $this;
            }

            /**
             * Search array and get key
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['a' => 1, 'b' => 2])->search(1); // a
             * arr(['a' => 1, 'b' => 2])->search('1'); // a
             * arr(['a' => 1, 'b' => 2])->search('1', true); // false
             * ```
             */
            public function search(mixed $value, bool $strict = false): int|string|bool
            {
                return \array_search($value, $this->arr, $strict);
            }

            /**
             * Shift an element off the beginning of array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])->shift(); // 1
             * ```
             */
            public function shift(): mixed
            {
                return \array_shift($this->arr);
            }

            /**
             * Sort an array in ascending order
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([3, 2, 1])->sort(); // [1, 2, 3]
             * ```
             */
            public function sort(int $flags = SORT_REGULAR): self
            {
                \sort($this->arr, $flags);

                return $this;
            }

            /**
             * Get array, same as `all`
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr()->toArray(); // []
             * arr([1, 2, 3])->toArray(); // [1, 2, 3]
             * ```
             */
            public function toArray(): array
            {
                return $this->arr;
            }

            /**
             * Convert array to json string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr()->toJson(); // '[]'
             * arr([1, 2, 3])->toJson(); // '[1,2,3]'
             * arr(['foo' => 'bar', 1])->toJson(); // {"foo":"bar","0":1}
             * ```
             */
            public function toJson(int $flags = 0, int $depth = 512): string|false
            {
                return \json_encode($this->arr, $flags, $depth);
            }

            /**
             * Transform array items
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])->transform(fn($i) => $i * 10)->all(); // [10, 20, 30]
             * ```
             */
            public function transform(callable $callback): self
            {
                foreach ($this->arr as $key => $item) {
                    $this->arr[$key] = $callback($item, $key);
                }

                return $this;
            }

            /**
             * Apply the callback if the value is false
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])->unless(false, fn($arr) => $arr->transform(...));
             * ```
             */
            public function unless(bool $boolean, callable $callback): self
            {
                return $this->when(! $boolean, $callback);
            }

            /**
             * Get array values
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr(['k1' => 'v1', 'k2' => 'v2'])->values()->all(); // ['v1', 'v2']
             * ```
             */
            public function values(): self
            {
                $this->arr = \array_values($this->arr);

                return $this;
            }

            /**
             * Apply the callback if the value is true
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])->when(true, fn($arr) => $arr->transform(...));
             * ```
             */
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

            public function __call(string $method, array $args): mixed
            {
                if (isset(self::$methods[$method])) {
                    return self::$methods[$method]->call($this, ...$args);
                }

                throw new \Error("Call to undefined method {$method}()");
            }

            /**
             * Register specific method for arr
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr()::register('getAll', function(): array {
             *     return $this->sort()->values()->all();
             * });
             *
             * arr()->getAll();
             * ```
             */
            public static function register(string $method, \Closure $closure): void
            {
                self::$methods[$method] = $closure;
            }

            /**
             * Set pagination component data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $pagination = Helper\paginate(1000);
             * arr($data)->setPagination($pagination);
             * ```
             */
            public function setPagination(object $pagination): self
            {
                $this->pagination = $pagination;

                return $this;
            }

            /**
             * Get pagination component data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr($data)->getPagination();
             * arr($data)->getPagination()->getLinks();
             * ```
             */
            public function getPagination(): object
            {
                return $this->pagination;
            }
        };
    }

    /**
     * The str method is a wrapper for strings.
     *
     * {@inheritDoc} **Examples:**
     * ```php
     * $data = 'Hello world!';
     * Helper\str($data)->endsWith('!'); // true
     * Helper\str($data)->contains('world'); // true
     * Helper\str($data)->append('Message: ')->get(); // Message: Hello world!
     * Helper\str($data)->replace('Hello ', '')->upper()->get(); // WORLD!
     *
     * // much more on documentation
     * ```
     */
    function str(mixed $str = ''): object
    {
        return new class ((string) $str) implements \Stringable, \Countable {
            /**
             * Registered methods
             *
             * @var array<string, \Closure>
             */
            protected static array $methods = [];

            /**
             * Constructor
             *
             * @param string $str
             */
            public function __construct(public string $str)
            {
                //
            }

            /**
             * Make and return new instance
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str()->make();
             * ```
             */
            public static function make(string $str): self
            {
                return new self($str);
            }

            /**
             * Append string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Hello')->append(' world!'); // Hello world!
             * ```
             */
            public function append(string $str): self
            {
                $this->str .= $str;

                return $this;
            }

            /**
             * Get path basename
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('/var/www/index.php')->basename(); // index.php
             * str('/var/www/index.php')->basename('.php'); // index
             * ```
             */
            public function basename(string $extension = ''): self
            {
                $this->str = \basename($this->str, $extension);

                return $this;
            }

            /**
             * Check string contains string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('/var/www/index.php')->contains('www'); // true
             * str('/var/www/index.php')->contains('foo'); // false
             * ```
             */
            public function contains(string $str): bool
            {
                return \str_contains($this->str, $str);
            }

            /**
             * Get directory name
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('/var/www/index.php')->dirname(); // /var/www
             * str('/var/www/index.php')->dirname(2); // /var
             * ```
             */
            public function dirname(int $level = 1): self
            {
                $this->str = \dirname($this->str, $level);

                return $this;
            }

            /**
             * Check string ends with string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('/var/www/index.php')->endsWith('.php'); // true
             * str('/var/www/index.php')->endsWith('foo'); // false
             * ```
             */
            public function endsWith(string $str): bool
            {
                return \str_ends_with($this->str, $str);
            }

            /**
             * Explode string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('a|b|c|d')->explode('/'); // ['a', 'b', 'c', 'd']
             * str('a|b|c|d', 3)->explode('/'); // ['a', 'b', 'c|d']
             * ```
             */
            public function explode(string $str, int $limit = \PHP_INT_MAX): array
            {
                if (empty($str)) {
                    return [];
                }

                return \explode($str, $this->str, $limit);
            }

            /**
             * Finish string with string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('file')->finish('.php'); // file.php
             * str('file.php')->finish('.php'); // file.php
             * ```
             */
            public function finish(string $str): self
            {
                if (! \str_ends_with($this->str, $str)) {
                    $this->str = $this->str . $str;
                }

                return $this;
            }

            /**
             * Lowercase first character of string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Hello')->lcfirst(); // hello
             * ```
             */
            public function lcfirst(): self
            {
                $firstChar = \mb_substr($this->str, 0, 1);
                $this->str = \mb_strtolower($firstChar) . \mb_substr($this->str, 1, null);

                return $this;
            }

            /**
             * Get string length
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Hello')->length(); // 5
             * ```
             */
            public function length(): int
            {
                return \mb_strlen($this->str);
            }

            /**
             * Limit string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Hello world!')->limit(5); // Hello...
             * str('Hello world!')->limit(5, '..'); // Hello..
             * ```
             */
            public function limit(int $limit, string $last = '...'): self
            {
                $this->str = \mb_substr($this->str, 0, $limit) . $last;

                return $this;
            }

            /**
             * Lowercase string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('FooBaR')->lower(); // foobar
             * ```
             */
            public function lower(): self
            {
                $this->str = \mb_convert_case($this->str, \MB_CASE_LOWER);

                return $this;
            }

            /**
             * Trim left side of string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str(' foo')->ltrim(); // 'foo'
             * str('foo')->ltrim('f') // 'oo'
             * ```
             */
            public function ltrim(?string $trim = " \n\r\t\v\x00"): self
            {
                $this->str = \ltrim($this->str, $trim);

                return $this;
            }

            /**
             * Prepend string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('bar')->prepend('foo'); // foobar
             * ```
             */
            public function prepend(string $str): self
            {
                $this->str = $str . $this->str;

                return $this;
            }

            /**
             * Remove string or character
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('foobarbaz')->remove('a'); // foobrbz
             * ```
             */
            public function remove(string $str): self
            {
                $this->str = \str_replace($str, '', $this->str);

                return $this;
            }

            /**
             * Replace string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('foobarbaz')->replace('foo', 'Hello '); // Hello barbaz
             * ```
             */
            public function replace(string $search, string $replace): self
            {
                $this->str = \str_replace($search, $replace, $this->str);

                return $this;
            }

            /**
             * Trim right side of string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('foo ')->ltrim(); // 'foo'
             * str('foo')->ltrim('o') // 'f'
             * ```
             */
            public function rtrim(?string $trim = " \n\r\t\v\x00"): self
            {
                $this->str = \rtrim($this->str, $trim);

                return $this;
            }

            /**
             * Reverse string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('reverse')->reverse(); // esrever
             * ```
             */
            public function reverse(): self
            {
                $this->str = \strrev($this->str);

                return $this;
            }

            /**
             * Slugify string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Lorem lipsum dolor sit amet!')->slugift('/');
             * // lorem-lipsum-dolor-sit-amet
             * ```
             */
            public function slugify(string $separator = '-'): self
            {
                $map = [
                    'ß' => 'ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
                    'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i',
                    'ï' => 'i', 'ð' => 'd', 'ñ' => 'n', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                    'ő' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y',
                    'þ' => 'th', 'ÿ' => 'y', 'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z',
                    'η' => 'h', 'θ' => '8', 'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3',
                    'ο' => 'o', 'π' => 'p', 'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x',
                    'ψ' => 'ps', 'ω' => 'w', 'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h',
                    'ώ' => 'w', 'ς' => 's', 'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i', 'ş' => 's', 'ı' => 'i',
                    'ğ' => 'g', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
                    'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
                    'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h',
                    'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
                    'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g', 'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n',
                    'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u', 'ž' => 'z', 'ą' => 'a', 'ć' => 'c', 'ę' => 'e',
                    'ł' => 'l', 'ń' => 'n', 'ś' => 's', 'ź' => 'z', 'ż' => 'z', 'ā' => 'a', 'ē' => 'e', 'ģ' => 'g',
                    'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n', 'ū' => 'u', 'i̇' => 'i',
                ];

                $this->str = \trim(\strtolower(
                    \preg_replace(
                        '/[^A-Za-z0-9-]+/',
                        $separator,
                        \strtr(\mb_strtolower($this->str), $map)
                    )
                ), $separator);

                return $this;
            }

            /**
             * Start string with string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('path')->start('/'); // /path
             * str('/path')->start('/'); // /path
             * ```
             */
            public function start(string $str): self
            {
                if (! \str_starts_with($this->str, $str)) {
                    $this->str = $str . $this->str;
                }

                return $this;
            }

            /**
             * Check string starts with string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('path')->startsWith('/'); // false
             * str('/path')->startsWith('/'); // true
             * ```
             */
            public function startsWith(string $str): bool
            {
                return \str_starts_with($this->str, $str);
            }

            /**
             * Return part of a string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('path')->substr(1); // ath
             * str('path')->substr(-1); // h
             * str('path')->substr(1, 2); // at
             * ```
             */
            public function substr(int $start, ?int $length = null): self
            {
                $this->str = \mb_substr($this->str, $start, $length);

                return $this;
            }

            /**
             * Translate string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Hello world!')->translate(['world' => 'earth']); // Hello earth!
             * ```
             */
            public function translate(array $values): self
            {
                $this->str = \strtr($this->str, $values);

                return $this;
            }

            /**
             * Trim string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str(' Hello world! ')->trim(); // 'Hello world!'
             * str('!Hello world!')->trim('!'); // 'Hello world'
             * ```
             */
            public function trim(?string $trim = " \n\r\t\v\x00"): self
            {
                $this->str = \trim($this->str, $trim);

                return $this;
            }

            /**
             * Convert string to Title case
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Hello world and earth!')->title(); // Hello World And Earth!
             * ```
             */
            public function title(): self
            {
                $this->str = \mb_convert_case($this->str, \MB_CASE_TITLE);

                return $this;
            }

            /**
             * Upper case first character of string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('hey yo!')->title(); // Hey yo!
             * ```
             */
            public function ucfirst(): self
            {
                $firstChar = \mb_substr($this->str, 0, 1);
                $this->str = \mb_strtoupper($firstChar) . \mb_substr($this->str, 1, null);

                return $this;
            }

            /**
             * Uppercase string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('hey!')->upper(); // HEY!
             * ```
             */
            public function upper(): self
            {
                $this->str = \mb_convert_case($this->str, \MB_CASE_UPPER);

                return $this;
            }

            /**
             * Apply the callback if the value is true
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Hello world!')->when(true, fn($str) => $str->upper()); // HELLO WORLD!
             * ```
             */
            public function when(bool $when, callable $callback): self
            {
                if ($when) {
                    $callback($this);
                }

                return $this;
            }

            /**
             * Get word count
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Hello world!')->wordCount(); // 2
             * ```
             */
            public function wordCount(): int
            {
                return \str_word_count($this->str);
            }

            /**
             * Wrap string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('foo')->wrap('#'); // #foo#
             * str('foo')->wrap('#', '@'); // #foo@
             * ```
             */
            public function wrap(string $start, ?string $end = null): self
            {
                if ($end === null) {
                    $end = $start;
                }

                $this->str = $start . $this->str . $end;

                return $this;
            }

            /**
             * Get string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Hello')->get(); // Hello
             * ```
             */
            public function get(): string
            {
                return $this->str;
            }

            /**
             * Get string length
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str('Hello')->count(); // 5
             * ```
             */
            public function count(): int
            {
                return $this->length();
            }

            public function __toString(): string
            {
                return $this->str;
            }

            public function __call(string $method, array $args): mixed
            {
                if (isset(self::$methods[$method])) {
                    return self::$methods[$method]->call($this, ...$args);
                }

                throw new \Error("Call to undefined method {$method}()");
            }

            /**
             * Register new method
             *
             * {@inheritDoc} **Example:**
             * ```php
             * str()::register('newMethod', fn($str) => $str->upper()->finish('#'));
             * str('foobar')->newMethod(); // FOOBAR#
             * ```
             */
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

                $messages = $_SESSION['__flash_' . $type] ?? null;
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
                } catch (\JsonException $e) {
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
        return new class (\intval($total), \intval($page), \intval($limit)) {
            public array $data;

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

            public function getData(): array
            {
                return $this->data;
            }

            public function getPage(): int
            {
                return $this->data['page'];
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

            public function getPageNumbers(): array
            {
                return \range(1, $this->getTotalPage());
            }
        };
    }
}
