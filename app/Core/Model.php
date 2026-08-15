<?php

namespace App\Core;

/**
 * Model
 *
 * Base model providing thin CRUD helpers over the Database layer.
 * Concrete models set the $table and optionally $fillable.
 */
abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';

    protected function db(): Database
    {
        return Database::instance();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->db()->fetch(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id",
            ['id' => $id]
        );
    }

    /**
     * @param array<string,mixed> $conditions
     * @return array<string,mixed>|null
     */
    public function findBy(array $conditions): ?array
    {
        [$where, $params] = $this->buildWhere($conditions);
        return $this->db()->fetch(
            "SELECT * FROM `{$this->table}` WHERE {$where} LIMIT 1",
            $params
        );
    }

    /**
     * @param array<string,mixed> $conditions
     * @return array<int,array<string,mixed>>
     */
    public function where(array $conditions, string $orderBy = '', int $limit = 0): array
    {
        [$where, $params] = $this->buildWhere($conditions);
        $sql = "SELECT * FROM `{$this->table}` WHERE {$where}";
        if ($orderBy !== '') {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        return $this->db()->fetchAll($sql, $params);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db()->fetchAll("SELECT * FROM `{$this->table}` ORDER BY {$orderBy}");
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        return $this->db()->insert($this->table, $data);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateById(int $id, array $data): int
    {
        return $this->db()->update($this->table, $data, [$this->primaryKey => $id]);
    }

    public function deleteById(int $id): int
    {
        return $this->db()->delete($this->table, [$this->primaryKey => $id]);
    }

    public function count(string $where = '1', array $params = []): int
    {
        return (int) $this->db()->scalar(
            "SELECT COUNT(*) FROM `{$this->table}` WHERE {$where}",
            $params
        );
    }

    /**
     * @param array<string,mixed> $conditions
     * @return array{0:string,1:array<string,mixed>}
     */
    protected function buildWhere(array $conditions): array
    {
        $parts  = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            $parts[] = "`{$col}` = :{$col}";
            $params[$col] = $val;
        }
        $where = $parts === [] ? '1' : implode(' AND ', $parts);
        return [$where, $params];
    }
}
