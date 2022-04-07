<?php

declare(strict_types=1);

namespace Helper
{
    use Pagination;
    use Mikro\Exceptions\ValidatorException;

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
                $item = $this->arr[$key] ?? $default;;
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

            /**
             * Get array or apply callback and get result
             *
             * {@inheritDoc} **Example:**
             * ```php
             * arr([1, 2, 3])(); // [1, 2, 3]
             * arr([1, 2, 3])(fn($arr) => $arr->keys()); // [0, 1, 2]
             * ```
             */
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

            /**
             * Register spesific method for arr
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
             * arr($data)->setPagination(Pagination\data());
             * ```
             */
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
};
