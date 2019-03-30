<?php
declare(strict_types=1);

namespace db;

use PDO;
use PDOStatement;

function connection($name = null)
{
    static $connections;
    static $default;

    $connections = $connections ?? [];
    $default = $default ?? 'default';
    
    if (!\is_array($name) && $name !== null) {
        $default = $name;
    }
    
    if (\is_array($name)) {
        $connections = \array_merge($connections, $name);
    } else {
        if (\array_key_exists($default, $connections)) {
            return $connections[$default];
        } else {
            throw new \Exception('Bağlantı mevcut değil: ' . $default);
        }
    }
}

function table(string $table, string $primaryKey = 'id')
{
    return new class($table, $primaryKey) {
        /** @var string */
        protected $table;

        /** @var string */
        protected $primaryKey;

        /** @var string */
        protected $select = '*';

        public function __construct(string $table, string $primaryKey)
        {
            $this->table = $table;
            $this->primaryKey = $primaryKey;
        }

        public function select(string $select)
        {
            $this->select = $select;

            return $this;
        }

        public function connection(string $name)
        {
            connection($name);

            return $this;
        }

        public function get(string $queryPart = '', array $params = [])
        {
            $query = "SELECT $this->select FROM $this->table $queryPart";
            return query($query, $params)->fetchAll();
        }

        public function find($queryPart = '', array $params = [])
        {
            if (\is_numeric($queryPart)) {
                $params[] = (int) $queryPart;
                $queryPart = "WHERE $this->primaryKey=?";
            }

            $query = "SELECT $this->select FROM $this->table $queryPart";
            return query($query, $params)->fetch();
        }

        public function insert(array $data): PDOStatement
        {
            return insert($this->table, $data);
        }

        public function update(
            array $data, 
            string $query = '', 
            array $params = []
        ): PDOStatement
        {
            if (\is_numeric($query)) {
                $params[] = $query;
                $query = "WHERE $this->primaryKey=?";
            }

            return update($this->table, $data, $query, $params);
        }

        public function delete($query = '', array $params = []): PDOStatement
        {
            if (\is_numeric($query)) {
                $params[] = $query;
                $query = "WHERE $this->primaryKey=?";
            }

            return delete($this->table, $query, $params);
        }

        public function paginate(string $query = '', array $params = [], array $options = [])
        {
            $options['totalItems'] = (int) query(
                "SELECT COUNT(*) FROM $this->table $query", $params
            )->fetchColumn();
            $pagination = \pagination\paginate($options);
            return query(
                "SELECT $this->select FROM $this->table $query LIMIT $pagination->limit", $params
            )->fetchAll();
        }
    };
}

function query(string $query, array $params = []): PDOStatement
{
    $sth = connection()->prepare($query);
    $sth->execute($params);
    return $sth;
}

function insert(string $table, array $data): PDOStatement
{
    $query = "INSERT INTO $table ";
    $query .= arrayToQuery($data, 'insert');
    return query($query, array_values($data));
}

function update(
    string $table, 
    array $data, 
    string $queryPart = '', 
    array $queryParams = []
): PDOStatement
{
    $query = "UPDATE $table SET ";
    $query .= arrayToQuery($data, 'update');
    $query .= " $queryPart";
    $params = \array_values($data);

    if (!empty($where)) {
        $query .= \sprintf(' %s', $queryPart);
        $params = \array_merge($params, $queryParams);
    }

    return query($query, $params);
}

function delete(
    string $table, 
    string $queryPart = '', 
    array $params = []
): PDOStatement
{
    $query = "DELETE FROM $table $queryPart";
    return query($query, $params);
}

function arrayToQuery(array $data, string $type): string
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
