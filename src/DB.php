<?php

declare(strict_types=1);

namespace DB
{
    use Helper;
    use Request;
    use Pagination;
    use Mikro\Exceptions\{MikroException, DataNotFoundException};

    /**
     * Get PDO connection
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\connection(); // Returns PDO instance
     * ```
     *
     * @throws MikroException When the PDO connection is not defined in the global $mikro array
     */
    function connection(): \PDO
    {
        global $mikro;

        if (! isset($mikro[CONNECTION]) || ! ($mikro[CONNECTION] instanceof \PDO)) {
            throw new MikroException('Create PDO instance first');
        }

        return $mikro[CONNECTION];
    }

    /**
     * Creates a table object where you can simply manage SQL Data
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $items = DB\table('items');
     * $items = DB\table('posts', 'post_id');
     *
     * // Retrieve items
     * DB\table('items')->get();
     * DB\table('items')->select('name')->get();
     * DB\table('items')->get('order by id desc');
     * DB\table('items')->find(5);
     * DB\table('items')->find('where id=?', [5]);
     * DB\table('items')->select('count(*)')->column();
     *
     * // Insert item
     * DB\table('items')->insert(['name' => 'foo', 'value' => 'bar']);
     * DB\last_insert_id();
     *
     * // Update item(s)
     * DB\table('items')->update(['name' => 'baz'], $id);
     * DB\table('items')->update(['name' => 'baz'], 'where id=?', [$id]);
     *
     * // Delete item(s)
     * DB\table('items')->delete(5);
     * DB\table('items')->delete('where id=?', [5]);
     *
     * // Paginate item(s)
     * DB\table('items')->paginate();
     * DB\table('items')->paginate('where id=?', [5], [
     *     'page' => Request\get('current_page'),
     *     'per_page' => 25
     * ])
     * DB\table('items')->paginate('', [], ['per_page' => 25]);
     *
     * Pagination\data(); // Gets pagination data
     * ```
     *
     * @throws MikroException When the PDO connection is not defined in the global $mikro array
     */
    function table(string $table, string $primaryKey = 'id'): object
    {
        return new class ($table, $primaryKey) {
            protected string $select = '*';

            public function __construct(
                protected string $table,
                protected string $primaryKey
            ) {
                //
            }

            public function select(string $select): self
            {
                $this->select = $select;

                return $this;
            }

            public function get(string $query = '', array $params = []): \Iterator
            {
                $query = "SELECT {$this->select} FROM {$this->table} {$query}";

                return Helper\arr(query($query, $params)->fetchAll(
                    \PDO::FETCH_PROPS_LATE | \PDO::FETCH_CLASS,
                    Helper\arr()::class
                ));
            }

            public function find(int|string $query = '', array $params = []): ?\Iterator
            {
                if (\is_numeric($query)) {
                    $params[$this->primaryKey] = (int) $query;
                    $query = "WHERE {$this->primaryKey}=:{$this->primaryKey}";
                }

                $query = "SELECT {$this->select} FROM {$this->table} {$query}";
                $result = query($query, $params)->fetch(\PDO::FETCH_NAMED);

                return $result ? Helper\arr($result) : null;
            }

            public function findOrFail(int|string $query = '', array $params = []): \Iterator
            {
                $result = $this->find($query, $params);

                if ($result === null) {
                    throw new DataNotFoundException();
                }

                return $result;
            }

            public function column(string $query = '', array $params = []): mixed
            {
                $query = "SELECT {$this->select} FROM {$this->table} {$query}";

                return query($query, $params)->fetchColumn();
            }

            public function insert(array $data, string $query = ''): \PDOStatement
            {
                return insert($this->table, $data, $query);
            }

            public function update(
                array $data,
                string $query = '',
                array $params = []
            ): \PDOStatement {
                if (\is_numeric($query)) {
                    $params[$this->primaryKey] = (int) $query;
                    $query = "WHERE {$this->primaryKey}=:{$this->primaryKey}";
                }

                return update($this->table, $data, $query, $params);
            }

            public function delete(int|string $query = '', array $params = []): \PDOStatement
            {
                if (\is_numeric($query)) {
                    $params[$this->primaryKey] = $query;
                    $query = "WHERE {$this->primaryKey}=:{$this->primaryKey}";
                }

                return delete($this->table, $query, $params);
            }

            public function paginate(string $query = '', array $params = [], array $options = []): \Iterator
            {
                $total = (int) query(
                    "SELECT COUNT(*) FROM {$this->table} {$query}",
                    $params
                )->fetchColumn();

                $pagination = Pagination\paginate(
                    $total,
                    (int) ($options['page'] ?? Request\get('page', 1)),
                    (int) ($options['per_page'] ?? 10)
                );

                $limit = "{$pagination['offset']},{$pagination['limit']}";

                $items = query(
                    "SELECT {$this->select} FROM {$this->table} {$query} LIMIT $limit",
                    $params
                )->fetchAll(
                    \PDO::FETCH_PROPS_LATE | \PDO::FETCH_CLASS,
                    Helper\arr()::class
                );

                return Helper\arr($items)->setPagination($pagination);
            }
        };
    }

    /**
     * Executes an SQL query with parameters
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\query('select * from items')->fetchAll();
     * DB\query('select * from items where id=?', [$id])->fetch();
     * DB\query('insert into items (name, value) values (?, ?)', [$name, $value]);
     * ```
     */
    function query(string $query, array $params = []): \PDOStatement
    {
        $sth = connection()->prepare($query);
        $sth->execute($params);

        return $sth;
    }

    /**
     * Executes an SQL query
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\exec('create table if not exists items ...');
     * ```
     */
    function exec(string $query): int|bool
    {
        return connection()->exec($query);
    }

    /**
     * Inserts data to the specified table
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\insert('items', ['name' => 'foo', 'value' => 'bar']);
     * ```
     */
    function insert(string $table, array $data, string $queryPart = ''): \PDOStatement
    {
        $query = "INSERT INTO {$table} ";
        $query .= querify($data, 'insert');
        $query .= empty($queryPart) ? '' : ' {$queryPart}';

        return query($query, \array_values($data));
    }

    /**
     * Updates the data(s) in the specified table
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\update('items', ['name' => 'foo', 'value' => 'bar']);
     * DB\update('items', ['name' => 'foo', 'value' => 'bar'], 'where id=?', [$id]);
     * ```
     */
    function update(
        string $table,
        array $data,
        string $query = '',
        array $parameters = []
    ): \PDOStatement {
        $query = "UPDATE {$table} SET " . querify($data, 'update') . " {$query}";
        $params = \array_values($data);

        if (! empty($parameters)) {
            $params = \array_merge($params, $parameters);
        }

        return query($query, $params);
    }

    /**
     * Deletes the data(s) from the specified table
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\delete('items');
     * DB\delete('items', 'where id=?', [$id]);
     * ```
     */
    function delete(
        string $table,
        string $query = '',
        array $parameters = []
    ): \PDOStatement {
        $query = "DELETE FROM {$table} {$query}";

        return query($query, $parameters);
    }

    /**
     * Get last insert id after insert query
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\last_insert_id();
     * ```
     */
    function last_insert_id(): string|bool
    {
        return connection()->lastInsertId();
    }

    /**
     * Smart data collection
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $collection = DB\collection([1, 2, 3, 4, 5, 6]);
     * $collection->paginate($currentPage = 1, $perPage = 2);
     * $collection->pages(); // [1, 2, 3]
     * $collection->page(1); [1, 2]
     * $collection->page(2); [3, 4]
     * $newCollection = $collection->map(function ($item) {
     *     return $item < 3 ? null : $item;
     * });
     * $collection->each(fn($item) => $item < 3 ? null : $item);
     * // [null, null, 3, 4, 5, 6]
     * $collection->isEmpty(); // false
     * ```
     */
    function collection(array $items, array $pagination = []): \Iterator
    {
        return new class ($items, 0, $pagination) implements \Iterator, \ArrayAccess, \Countable {
            public function __construct(
                private array $collection,
                private int $position = 0,
                public array $pagination = []
            ) {
                //
            }

            public function rewind(): void
            {
                $this->position = 0;
            }

            public function current(): mixed
            {
                return $this->collection[$this->position];
            }

            public function key(): mixed
            {
                return $this->position;
            }

            public function next(): void
            {
                ++$this->position;
            }

            public function valid(): bool
            {
                return isset($this->collection[$this->position]);
            }

            public function count(): int
            {
                return \count($this->collection);
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
                if ($offset === null) {
                    $this->collection[] = $value;
                } else {
                    $this->collection[$offset] = $value;
                }
            }

            public function offsetExists(mixed $offset): bool
            {
                return isset($this->collection[$offset]);
            }

            public function offsetUnset(mixed $offset): void
            {
                unset($this->collection[$offset]);
            }

            public function offsetGet(mixed $offset): mixed
            {
                return isset($this->collection[$offset]) ? $this->collection[$offset] : null;
            }

            public function map(callable $callback): self
            {
                $new = \array_map($callback, $this->collection);

                return new self($new);
            }

            public function each(callable $callback): self
            {
                foreach ($this->collection as $key => $item) {
                    $this->collection[$key] = $callback($item);
                }

                return $this;
            }

            public function chunk(int $size): self
            {
                $this->collection = \array_chunk($this->collection, $size);

                return $this;
            }

            public function paginate(int $perPage = 10, int $currentPage = 1): self
            {
                if (empty($this->pagination)) {
                    $this->pagination = Pagination\paginate(
                        $this->count(),
                        $currentPage,
                        $perPage
                    );
                }

                $this->collection = $this->chunk($perPage)->find($currentPage - 1) ?? [];

                return $this;
            }

            public function pages(): array
            {
                return ! empty($this->pagination) ?
                    \range(1, $this->pagination['total_page']) : [];
            }

            public function links(array $options = []): string
            {
                return ! empty($this->pagination) ?
                    Pagination\links($this->pagination, $options) : '';
            }

            public function isEmpty(): bool
            {
                return empty($this->collection);
            }

            public function first(): mixed
            {
                return $this->collection[\array_key_first($this->collection)];
            }

            public function last(): mixed
            {
                return $this->collection[\array_key_last($this->collection)];
            }

            public function find(mixed $key, mixed $default = null): mixed
            {
                return $this->collection[$key] ?? $default;
            }

            public function toArray(): array
            {
                return $this->collection;
            }
        };
    }

    /**
     * @internal
     */
    function querify(array $data, string $type): string
    {
        $string = '';

        switch ($type) {
            case 'insert':
                $arrayParameters = \array_values($data);
                $columnsString = \implode(',', \array_keys($data));
                $valuesString = \implode(',', \array_fill(0, \count($arrayParameters), '?'));
                $string = "({$columnsString}) VALUES ({$valuesString})";
                break;
            case 'update':
                foreach ($data as $key => $value) {
                    $string .= "{$key}=?,";
                }

                $string = \rtrim($string, ',');
                break;
        }

        return $string;
    }

    /**
     * DB connection constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[DB\CONNECTION] = new PDO('...');
     * ```
     */
    const CONNECTION = 'DB\CONNECTION';
};
