<?php

declare(strict_types=1);

namespace DB
{
    use Helper;
    use Pagination;
    use Mikro\Exceptions\{MikroException, DataNotFoundException};

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
     * DB\table('items')->orderBy('id DESC')->get();
     * DB\table('items')->find(5);
     * DB\table('items')->where('id=:id')->bind(':id', 5)->find();
     * DB\table('items')->select('count(*)')->column();
     * DB\table('items')->count();
     *
     * // Insert item
     * DB\table('items')->fill(['name' => 'foo', 'value' => 'bar'])->insert();
     * DB\last_insert_id();
     *
     * // Update item(s)
     * DB\table('items')
     *     ->fill(['name' => 'baz'])
     *     ->where('id=:id')
     *     ->bind(':id', $id)
     *     ->update();
     *
     * // Delete item(s)
     * DB\table('items')->where('id=:id')->bind(':id', $id)->delete();
     *
     * // Paginate item(s)
     * DB\table('items')->paginate();
     * DB\table('items')->where('type=:type')->bind(':type', 'footype')
     *     ->paginate($currentPage = 1, $perPage = 10)
     * $items = DB\table('items')->paginate(perPage: 25);
     *
     * $items->getPagination()->getPages(); // Gets pagination pages as array
     * $items->getPagination()->getLinks(); // Gets pagination data as string
     * ```
     *
     * @throws MikroException When the PDO connection is not defined in the global $mikro array
     */
    function table(string $table, string $primaryKey = 'id'): object
    {
        return new class ($table, $primaryKey) {
            /**
             * Builder object
             *
             * @var object $builder
             */
            protected object $builder;

            /**
             * Model data
             *
             * @var array $model
             */
            protected array $model = [];

            /**
             * Model attributes
             *
             * @var array $attributes
             */
            protected array $attributes = [];

            public function __construct(
                protected string $table,
                protected string $primaryKey = 'id',
            ) {
                global $mikro;

                $this->builder = builder();
                $this->builder->table($this->table, '*');

                if (isset($mikro[MODELS][$this->table])) {
                    $this->model = $mikro[MODELS][$this->table];
                    $this->buildModel();
                }
            }

            /**
             * Get executed statement
             *
             * {@inheritDoc} **Example:**
             * ```php
             * DB\table('items')->getStatement()->fetchAll();
             * ```
             */
            public function getStatement(): \PDOStatement
            {
                $query = $this->builder->build();
                $statement = connection()->prepare($query['sql']);

                foreach ($query['parameters'] as $parameter) {
                    $statement->bindParam(...$parameter);
                }

                $statement->execute();

                return $statement;
            }

            /**
             * Get multiple query result
             *
             * {@inheritDoc} **Example:**
             * ```php
             * DB\table('items')->get();
             * DB\table('items')->where('status=?', [$status])->get();
             * ```
             */
            public function get(): \Iterator
            {
                return Helper\arr(
                    $this->getStatement()->fetchAll(
                        \PDO::FETCH_PROPS_LATE | \PDO::FETCH_CLASS,
                        Helper\arr()::class
                    )
                )->transform([$this, 'applyGetter']);
            }

            /**
             * Get singular query result
             *
             * {@inheritDoc} **Example:**
             * ```php
             * DB\table('items')->find($id);
             * DB\table('items')->where('id=?', [$id])->find();
             * ```
             */
            public function find(?int $primaryKey = null): ?\Iterator
            {
                if ($primaryKey) {
                    $this->builder
                        ->where($this->primaryKey . '=:' . $this->primaryKey)
                        ->bindInt(':' . $this->primaryKey, $primaryKey);
                }

                $result = $this->getStatement()->fetch(\PDO::FETCH_NAMED);

                return $result ? $this->applyGetter(Helper\arr($result)) : null;
            }

            /**
             * Get singular query result or throw an exception
             *
             * {@inheritDoc} **Example:**
             * ```php
             * DB\table('items')->findOrFail($id);
             * DB\table('items')->where('id=?', [$id])->findOrFail();
             * ```
             */
            public function findOrFail(?int $primaryKey = null): \Iterator
            {
                $result = $this->find($primaryKey);

                if ($result === null) {
                    throw new DataNotFoundException();
                }

                return $result;
            }

            /**
             * Execute query and get column
             *
             * {@inheritDoc} **Example:**
             * ```php
             * DB\table('items')->select('COUNT(*)')->column();
             * ```
             */
            public function column(): mixed
            {
                return $this->getStatement()->fetchColumn();
            }

            /**
             * Get item count
             *
             * {@inheritDoc} **Example:**
             * ```php
             * DB\table('items')->count();
             * DB\table('items')->where('status=5')->count();
             * ```
             */
            public function count(): int
            {
                $this->builder->select('COUNT(*)');

                return $this->getStatement()->fetchColumn();
            }

            /**
             * Paginate data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $items = DB\table('items')->paginate();
             * $items = DB\table('items')->paginate(Request\input('page', 1), 25);
             *
             * $pageLinks = $items->getPagination()->getLinks();
             * $pageLinksRendered = $items->getPagination()->getLinks();
             * ```
             */
            public function paginate(int|string $currentPage = 1, int|string $perPage = 10): \Iterator
            {
                $pagination = Helper\paginate(
                    (clone $this)->count(),
                    (int) $currentPage,
                    (int) $perPage
                );

                $this->builder->limit(\sprintf('%u, %u', $pagination->getOffset(), $pagination->getLimit()));

                return Helper\arr(
                    $this->getStatement()->fetchAll(
                        \PDO::FETCH_PROPS_LATE | \PDO::FETCH_CLASS,
                        Helper\arr()::class
                    )
                )->setPagination($pagination)->transform([$this, 'applyGetter']);
            }

            /**
             * Insert filled data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $item = DB\table('items');
             * $item->name = 'foo';
             * $item->price = 100;
             * $item->insert();
             *
             * // or
             *
             * DB\table('items')->fill(['name' => 'foo', 'price' => 100])->insert();
             *
             * DB\last_insert_id();
             * ```
             */
            public function insert(): ?\PDOStatement
            {
                if ($this->applyEvent('inserting', $this->attributes) === false) {
                    return null;
                }

                $data = $this->getFillableAttributes();
                $keys = \trim(\implode(', ', \array_keys($data)), ', ');
                $values = \array_map(fn($item) => ':' . $item, \array_keys($data));

                $this->builder
                    ->insertInto(\sprintf('%s (%s)', $this->table, $keys))
                    ->valuesArray($values);

                foreach ($data as $key => $value) {
                    $this->builder->bind(':' . $key, $value);
                }

                $statement = $this->getStatement();

                $this->attributes[$this->primaryKey] = last_insert_id();
                $this->applyEvent('inserted', $this->attributes);

                return $statement;
            }

            /**
             * Update filled data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $item = DB\table('items');
             * $item->price = 305;
             * $item->where('id=:id', [':id' => $id])->update();
             *
             * // or
             *
             * DB\table('items')
             *     ->fill(['price' => 110])
             *     ->where('id=:id', [':id' => $id])
             *     ->update();
             * ```
             */
            public function update(): ?\PDOStatement
            {
                if ($this->applyEvent('updating', $this->attributes) === false) {
                    return null;
                }

                $data = $this->getFillableAttributes();

                $this->builder->update($this->table)->setArray(
                    Helper\arr($data)
                        ->mapWithKeys(fn($value, $key) => [$key => ':' . $key])
                        ->all()
                );

                foreach ($data as $key => $value) {
                    $this->builder->bind(':' . $key, $value);
                }

                $statement = $this->getStatement();

                $this->applyEvent('updated', $this->attributes);

                return $statement;
            }

            /**
             * Update data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * DB\table('items')->where('id=?', [$id])->delete();
             * ```
             */
            public function delete(): ?\PDOStatement
            {
                if ($this->applyEvent('deleting', $this->attributes) === false) {
                    return null;
                }

                $this->builder->deleteFrom($this->table);

                $statement = $this->getStatement();

                $this->applyEvent('deleted', $this->attributes);

                return $statement;
            }

            /**
             * Fill data
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $item = DB\table('items')->fill(['name' => 'foo', 'price' => 305]);
             *
             * $item->price; // 305
             * $item->name; // 'foo'
             * ```
             */
            public function fill(array $attributes): self
            {
                foreach ($attributes as $key => $attribute) {
                    $attributes[$key] = $this->applySetter($key, $attribute);
                }

                $this->attributes = \array_replace($this->attributes, $attributes);

                return $this;
            }

            public function __call(string $method, array $args): mixed
            {
                $builderMethods = [
                    'bindBool', 'bindNull', 'bindInt', 'bindStr', 'bindStrNatl',
                    'bindStrChar', 'bindLob', 'bindStmt', 'innerJoin', 'crossJoin',
                    'leftJoin', 'rightJoin', 'outerJoin'
                ];

                if (
                    \method_exists($this->builder, $method) ||
                    \in_array($method, $builderMethods)
                ) {
                    $this->builder->{$method}(...$args);

                    return $this;
                }

                if (isset($this->model[$method]) && $this->model[$method] instanceof \Closure) {
                    return $this->model[$method]->call($this, ...$args);
                }

                throw new \Error("Call to undefined method {$method}");
            }

            public function __clone(): void
            {
                $this->builder = clone $this->builder;
            }

            /**
             * Apply defined model
             */
            protected function buildModel(): void
            {
                if (isset($this->model['table'])) {
                    $this->table = $this->model['table'];
                }

                if (isset($this->model['primary_key'])) {
                    $this->primaryKey = $this->model['primary_key'];
                }
            }

            /**
             * Apply defined getters
             */
            public function applyGetter(object $item): object
            {
                foreach ($item as $key => $value) {
                    if (
                        isset($this->model['get_' . $key]) &&
                        $this->model['get_' . $key] instanceof \Closure
                    ) {
                        $item[$key] = $this->model['get_' . $key]->call($this, $value);
                    }
                }

                if (
                    isset($this->model['hidden']) &&
                    \is_array($this->model['hidden']) &&
                    ! empty($this->model['hidden'])
                ) {
                    foreach ($this->model['hidden'] as $hidden) {
                        if ($item->has($hidden)) {
                            $item->forget($hidden);
                        }
                    }
                }

                return $item;
            }

            /**
             * Apply defined setters
             */
            public function applySetter(string $key, mixed $value): mixed
            {
                if (
                    isset($this->model['set_' . $key]) &&
                    $this->model['set_' . $key] instanceof \Closure
                ) {
                    return $this->model['set_' . $key]->call($this, $value);
                }

                return $value;
            }

            /**
             * Apply defined events
             */
            protected function applyEvent(string $event, mixed $data): mixed
            {
                if (
                    isset($this->model['event_' . $event]) &&
                    $this->model['event_' . $event] instanceof \Closure
                ) {
                    return $this->model['event_' . $event]->call($this, $data);
                }

                return $data;
            }

            /**
             * Get fillable attributes
             */
            protected function getFillableAttributes(): array
            {
                if (
                    isset($this->model['fillable']) &&
                    \is_array($this->model['fillable']) &&
                    ! empty($this->model['fillable'])
                ) {
                    $newAttributes = [];

                    foreach ($this->model['fillable'] as $fillable) {
                        if (isset($this->attributes[$fillable])) {
                            $newAttributes[$fillable] = $this->attributes[$fillable];
                        }
                    }

                    return $newAttributes;
                }

                return $this->attributes;
            }

            public function __get(string $key): mixed
            {
                return $this->attributes[$key] ?? $this->model[$key] ??
                    throw new \ErrorException('Undefined property $' . $key);
            }

            public function __set(string $key, mixed $value): void
            {
                $this->attributes[$key] = $this->applySetter($key, $value);
            }

            public function __isset(string $key): bool
            {
                return isset($this->attributes[$key]);
            }

            public function __unset(string $key): void
            {
                unset($this->attributes[$key]);
            }
        };
    }

    /**
     * Simple query builder
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $sql = DB\builder()->select('title, body')->from('posts')->where('id=5')->orderBy('id DESC');
     * (string) $sql; // SELECT title, body FROM posts WHERE id=5 ORDER BY id DESC
     *
     * DB\builder()->insertInto('items (name, price)')->values('(:name, :price)');
     * DB\builder()->insertInto('items (name, price)')->valuesArray([':name', ':price']);
     * // INSERT INTO items (name, price) VALUES (:name, :price)
     *
     * DB\builder()->insertInto('items')->setArray(['foo' => 'bar', 'baz' => 5]);
     * // INSERT INTO items SET foo='bar', baz=5
     *
     * DB\builder()->update('table')->set('name=:name, foo=:foo');
     * DB\builder()->update('table')->setArray(['name' => ':name', 'foo' => ':foo']);
     * // UPDATE table SET name=:name, foo=:foo
     *
     * DB\builder()->deleteFrom('posts')->where('id=5');
     * // DELETE FROM posts WHERE id=5
     * ```
     */
    function builder(): object
    {
        return new class implements \Stringable {
            /**
             * Query type (select, insert, update or delete)
             *
             * @var string
             */
            protected string $type = '';

            /**
             * SELECT clauses
             *
             * @var array<string, string>
             */
            protected array $select = [
                'SELECT' => '',
                'FROM' => '',
                'JOIN' => '',
                'INNER JOIN' => '',
                'CROSS JOIN' => '',
                'LEFT JOIN' => '',
                'RIGHT JOIN' => '',
                'OUTER JOIN' => '',
                'WHERE' => '',
                'GROUP BY' => '',
                'HAVING' => '',
                'ORDER BY' => '',
                'LIMIT' => '',
            ];

            /**
             * INSERT INTO clauses
             *
             * @var array<string, string>
             */
            protected array $insert = [
                'INSERT INTO' => '',
                'SET' => '',
                'VALUES' => '',
                'ON DUPLICATE KEY UPDATE' => '',
            ];

            /**
             * UPDATE clauses
             *
             * @var array<string, string>
             */
            protected array $update = [
                'UPDATE' => '',
                'SET' => '',
                'WHERE' => '',
                'ORDER BY' => '',
                'LIMIT' => '',
            ];

            /**
             * DELETE clauses
             *
             * @var array<string, string>
             */
            protected array $delete = [
                'DELETE FROM' => '',
                'WHERE' => '',
                'ORDER BY' => '',
                'LIMIT' => '',
            ];

            /**
             * In query bindings collection
             *
             * @var array<string, array>
             */
            protected array $binds = [];

            /**
             * PDO compatible bindings
             *
             * @var array<array<string, mixed>>
             */
            protected array $parameters = [];

            /**
             * Binding sequence
             *
             * @var int
             */
            protected int $sequence = 1;

            /**
             * Make new query
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $builder = builder()->select('foo, bar');
             * $newBuilder = $builder->make()->select('*');
             * ```
             */
            public static function make(): self
            {
                return new self();
            }

            /**
             * Make SELECT statement
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->select('foo, bar as b');
             * ```
             */
            public function select(string $select): self
            {
                $this->setType('select');

                $this->select['SELECT'] = $select . ' ';

                return $this;
            }

            /**
             * Make from clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->select('*')->from('table');
             * ```
             */
            public function from(string $from): self
            {
                $this->select['FROM'] = $from . ' ';

                return $this;
            }

            /**
             * Make SELECT statement and FROM clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->table('items', '*');
             * ```
             */
            public function table(string $from, ?string $select = null): self
            {
                if ($select) {
                    $this->select($select);
                } else {
                    $this->setType('select');
                }

                $this->from($from);

                return $this;
            }

            /**
             * Make JOIN clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->table('items', '*')->join('table on ...');
             * ```
             */
            public function join(string $join, array $binds = []): self
            {
                $this->select['JOIN'] .= $join . ' ';
                $this->mergeBinds('JOIN', $binds);

                return $this;
            }

            /**
             * Make WHERE clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->table('items', '*')->where('id=100');
             * ```
             */
            public function where(string $where, array $binds = []): self
            {
                $this->checkType();
                $this->checkAvailability('WHERE');

                $this->{$this->type}['WHERE'] .= $where . ' ';
                $this->mergeBinds('WHERE', $binds);

                return $this;
            }

            /**
             * Make GROUP BY clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->table('items', '*')->groupBy('column');
             * ```
             */
            public function groupBy(string $groupBy, array $binds = []): self
            {
                $this->select['GROUP BY'] .= $groupBy . ' ';
                $this->mergeBinds('GROUP BY', $binds);

                return $this;
            }

            /**
             * Make HAVING clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->table('items', '*')->having('column=value');
             * ```
             */
            public function having(string $having, array $binds = []): self
            {
                $this->select['HAVING'] .= $having . ' ';
                $this->mergeBinds('HAVING', $binds);

                return $this;
            }

            /**
             * Make ORDER BY clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->table('items', '*')->orderBy('id DESC');
             * ```
             */
            public function orderBy(string $orderBy, array $binds = []): self
            {
                $this->checkType();
                $this->checkAvailability('ORDER BY');

                $this->{$this->type}['ORDER BY'] .= $orderBy . ' ';
                $this->mergeBinds('ORDER BY', $binds);

                return $this;
            }

            /**
             * Make LIMIT clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->table('items', '*')->limit('100');
             * ```
             */
            public function limit(string $limit, array $binds = []): self
            {
                $this->checkType();
                $this->checkAvailability('LIMIT');

                $this->{$this->type}['LIMIT'] .= $limit . ' ';
                $this->mergeBinds('LIMIT', $binds);

                return $this;
            }

            /**
             * Make INSERT INTO statement
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->insertInto('items');
             builder()->insertInto('items (name, price)');
             * ```
             */
            public function insertInto(string $insert, array $binds = []): self
            {
                $this->setType('insert');

                $this->insert['INSERT INTO'] .= $insert . ' ';
                $this->mergeBinds('INSERT INTO', $binds);

                return $this;
            }

            /**
             * Make VALEUS clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->insertInto('items (name, price)')->values('(:name, :price)');
             * ```
             */
            public function values(string $values, array $binds = []): self
            {
                $this->insert['VALUES'] .= $values . ' ';
                $this->mergeBinds('VALUES', $binds);

                return $this;
            }

            /**
             * Make VALEUS clause with array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->insertInto('items (name, price)')->valuesArray([':name', ':price']);
             * ```
             */
            public function valuesArray(array $values, array $binds = []): self
            {
                $string = '';

                foreach ($values as $value) {
                    if (\is_numeric($value) || $value === '?' || \str_starts_with($value, ':')) {
                        $string .= $value . ', ';
                    } else {
                        $string .= "'{$value}', ";
                    }
                }

                return $this->values('(' . \trim($string, ', ') . ')', $binds);
            }

            /**
             * Make ON DUPLICATE KEY UPDATE clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()
             *     ->insertInto('items (name, price)')
             *     ->valuesArray([':name', ':price'])
             *     ->onDuplicateKeyUpdate('foo=:bar');
             * ```
             */
            public function onDuplicateKeyUpdate(string $onDuplicateKeyUpdate, array $binds = []): self
            {
                $this->insert['ON DUPLICATE KEY UPDATE'] .= $onDuplicateKeyUpdate . ' ';
                $this->mergeBinds('ON DUPLICATE KEY UPDATE', $binds);

                return $this;
            }

            /**
             * Make UPDATE statement
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->update('items');
             * ```
             */
            public function update(string $update, array $binds = []): self
            {
                $this->setType('update');

                $this->update['UPDATE'] .= $update . ' ';
                $this->mergeBinds('UPDATE', $binds);

                return $this;
            }

            /**
             * Make SET clause
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->update('items')->set('foo=:foo, bar=5');
             * builder()->insertInto('items')->set('foo=:foo, bar=5');
             * ```
             */
            public function set(string $set, array $binds = []): self
            {
                $this->checkType();
                $this->checkAvailability('SET');

                $this->{$this->type}['SET'] .= $set . ' ';
                $this->mergeBinds('SET', $binds);

                return $this;
            }

            /**
             * Make SET clause with array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->update('items')->setArray(['foo' => ':foo', 'bar' => 5']);
             * builder()->insertInto('items')->setArray(['foo' => ':foo', 'bar' => 5']);
             * ```
             */
            public function setArray(array $values, array $binds = []): self
            {
                $string = '';

                foreach ($values as $key => $value) {
                    if (\is_numeric($value) || $value === '?' || \str_starts_with($value, ':')) {
                        $string .= "{$key}={$value}, ";
                    } else {
                        $string .= "{$key}='{$value}', ";
                    }
                }

                return $this->set(\trim($string, ', '), $binds);
            }

            /**
             * Make DELETE FROM statement
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->deleteFrom('items');
             * ```
             */
            public function deleteFrom(string $deleteFrom, array $binds = []): self
            {
                $this->setType('delete');

                $this->delete['DELETE FROM'] .= $deleteFrom . ' ';
                $this->mergeBinds('DELETE FROM', $binds);

                return $this;
            }

            /**
             * Set active query type (select, insert, update or delete)
             */
            protected function setType(string $type): void
            {
                $oldType = $this->type;
                $this->type = $type;

                $common = ['SET', 'WHERE', 'ORDER BY', 'LIMIT'];

                foreach ($common as $item) {
                    if (! empty($this->{$oldType}[$item])) {
                        $this->{$type}[$item] = $this->{$oldType}[$item];
                    }
                }
            }

            /**
             * Check query type is set
             */
            protected function checkType(): void
            {
                if (empty($this->type)) {
                    throw new \Exception('Please declare `select`, `insertInto` or `update` methods first');
                }
            }

            /**
             * Check statement/clause availability
             */
            protected function checkAvailability(string $statement): void
            {
                if (! isset($this->{$this->type}[$statement])) {
                    throw new \Exception(
                        \sprintf('%s is not available in %s query type', $statement, \strtoupper($this->type))
                    );
                }
            }

            /**
             * Bind parameter for PDO
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->bind(':id', 5, \PDO::PARAM_INT);
             * ```
             */
            public function bind(string|int|array $param, mixed $var = null, int $type = \PDO::PARAM_STR): self
            {
                if (\is_array($param) && $var === null) {
                    return $this->bindSequence($param, $type);
                }

                $this->parameters[$param] = \compact('param', 'var', 'type');

                return $this;
            }

            /**
             * Bind parameter with sequence
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->bindSequence(['parameter value 1', 'parameter value 2']);
             * ```
             */
            public function bindSequence(array $binds, int $type = \PDO::PARAM_STR): self
            {
                foreach ($binds as $bind) {
                    $this->bind($this->sequence, $bind, $type);
                    $this->sequence++;
                }

                return $this;
            }

            /**
             * Bind multiple parameter with key-value array
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->binds([':foo' => 'foo', ':bar' => 5]);
             * ```
             */
            public function binds(array $binds): self
            {
                foreach ($binds as $key => $value) {
                    if (\is_int($key) && \is_array($value)) {
                        $this->bind(...$value);
                    } else {
                        $this->bind($key, $value);
                    }
                }

                return $this;
            }

            /**
             * Merge in query bindings
             */
            protected function mergeBinds(string $query, array $binds = []): void
            {
                if (! \array_is_list($binds)) {
                    $this->binds($binds);
                } elseif (
                    isset($this->binds[$this->type][$query]) &&
                    ! empty($this->binds[$this->type][$query])
                ) {
                    $this->binds[$this->type][$query] =
                        \array_merge($this->binds[$this->type][$query], $binds);
                } else {
                    $this->binds[$this->type][$query] = $binds;
                }
            }

            /**
             * Get defined parameters
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->getParameters(); // array
             * ```
             */
            public function getParameters(): array
            {
                return $this->parameters;
            }

            /**
             * Convert to SQL string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * (string) builder()->table('items', '*');
             * // SELECT * FROM items
             * ```
             */
            public function __toString(): string
            {
                $this->checkType();

                $result = '';

                foreach ($this->{$this->type} as $key => $value) {
                    if (! empty(\trim($value))) {
                        $result .= \sprintf('%s %s ', $key, \trim($value));
                    }

                    if (isset($this->binds[$this->type][$key])) {
                        $this->bind($this->binds[$this->type][$key]);
                    }
                }

                return \trim($result);
            }

            /**
             * Convert to SQL string
             *
             * {@inheritDoc} **Example:**
             * ```php
             * builder()->table('items', '*')->toSql();
             * // SELECT * FROM items
             * ```
             */
            public function toSql(): string
            {
                return $this->__toString();
            }

            /**
             * Get SQL and parameters
             *
             * {@inheritDoc} **Example:**
             * ```php
             * $builder = builder()
             *     ->table('items', '*')
             *     ->where('id=:id')
             *     ->bind(':id', 5, \PDO::PARAM_INT);
             * $builder->build();
             * // [
             * //     'sql' => 'SELECT * from items WHERE id=:id',
             * //     'parameters' => [
             * //         ['param' => ':id', 'var' => 5, 'type' => 1]
             * //     ]
             * // ]
             * ```
             */
            public function build(): array
            {
                return [
                    'sql' => $this->toSql(),
                    'parameters' => $this->parameters
                ];
            }

            public function __call(string $method, array $args): self
            {
                if (\str_ends_with($method, 'Join')) {
                    $type = \strtoupper(\str_replace('Join', '', $method));

                    if (isset($this->select[$type . ' JOIN'])) {
                        if (! isset($args[0])) {
                            throw new \ArgumentCountError("Too few arguments to method {$method}, 0 passed");
                        }

                        if (! is_string($args[0])) {
                            throw new \TypeError('Argument #1 must be of type string');
                        }

                        $this->select[$type . ' JOIN'] .= $args[0];

                        if (isset($args[1]) && \is_array($args[1]) && ! empty($args[1])) {
                            $this->mergeBinds($type . ' JOIN', $args[1]);
                        }

                        return $this;
                    }
                }

                if (\str_starts_with($method, 'bind')) {
                    $methods = ['Bool', 'Null', 'Int', 'Str', 'StrNatl', 'StrChar', 'Lob', 'Stmt'];
                    $last = \str_replace('bind', '', $method);

                    if (in_array($last, $methods)) {
                        if ($last === 'StrNatl' || $last === 'StrChar') {
                            $last = \str_replace('Str', 'Str_', $last);
                        }

                        $constant = 'PDO::PARAM_' . \strtoupper($last);

                        if (\count($args) !== 2) {
                            throw new \ArgumentCountError("Too few arguments to method {$method}");
                        }

                        if (! \is_string($args[0]) && ! \is_int($args[0])) {
                            throw new \TypeError('Argument #1 must be of type string|int');
                        }

                        return $this->bind($args[0], $args[1], \constant($constant));
                    }
                }

                throw new \Error("Call to undefined method {$method}");
            }
        };
    }

    /**
     * Defines table model and options
     *
     * {@inheritDoc} **Example:**
     * ```php
     * DB\model('items', [
     *     'table' => 'items', // sets table name
     *     'primary_key' => 'id', // sets table primary key
     *     'fillable' => ['fillable_field_name', 'title', 'foo', 'bar_id'], // set fillable attributes;
     *     'hidden' => ['password'], // set hidden fields
     *     'set_title' => fn($title) => strtoupper($title), // title attribute setter
     *     'get_foo' => fn($foo) => $foo === 'x' ? 'Active' : 'Inactive', // foo attribute getter
     *     'event_inserting' => fn(array $attributes) => $attributes,
     *     'event_inserting' => fn(array $attributes) => false,
     *     'event_inserted' => fn(array $attributes) => $attributes,
     *     'event_updating' => fn(array $attributes) => $attributes,
     *     'event_updated' => fn(array $attributes) => $attributes,
     *     'event_deleting' => fn(array $attributes) => $attributes,
     *     'event_deleted' => fn(array $attributes) => $attributes,
     *     'spesificMethod' => function () {
     *         $this->builder->select('id, name')->orderBy('id DESC');
     *
     *         return $this->get(); // get all
     *     }, // DB\table('items')->spesificMethod();
     *
     *     'findSlug' => function ($slug) {
     *         $this->builder->where('slug=:slug')->bind(':slug', $slug);
     *
     *         return $this->find(); // find slug
     *     } // DB\table('items')->findSlug($slug);
     * ]);
     * ```
     */
    function model(string $model, array $options): void
    {
        global $mikro;

        $mikro[MODELS][$model] = $options;
    }

    const MODELS = 'DB\MODELS';
}
