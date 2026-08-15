<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Database
 *
 * PDO wrapper (singleton) with prepared-statement helpers.
 * All queries use bound parameters — never string interpolation.
 */
class Database
{
    private static ?Database $instance = null;

    private PDO $pdo;

    private function __construct()
    {
        $host    = Config::get('db.host');
        $port    = Config::get('db.port');
        $name    = Config::get('db.name');
        $user    = Config::get('db.user');
        $pass    = Config::get('db.pass');
        $charset = Config::get('db.charset', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    public static function instance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Run a prepared statement.
     *
     * @param array<int|string,mixed> $params
     */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row.
     *
     * @param array<int|string,mixed> $params
     * @return array<string,mixed>|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Fetch all rows.
     *
     * @param array<int|string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single scalar column value.
     *
     * @param array<int|string,mixed> $params
     */
    public function scalar(string $sql, array $params = []): mixed
    {
        return $this->run($sql, $params)->fetchColumn();
    }

    /**
     * Insert a row and return the last insert id.
     *
     * @param array<string,mixed> $data
     */
    public function insert(string $table, array $data): int
    {
        $columns      = array_keys($data);
        $placeholders = array_map(static fn($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(static fn($c) => "`{$c}`", $columns)),
            implode(', ', $placeholders)
        );
        $this->run($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update rows matching a where clause.
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $where
     */
    public function update(string $table, array $data, array $where): int
    {
        $set = [];
        $params = [];
        foreach ($data as $col => $val) {
            $set[] = "`{$col}` = :set_{$col}";
            $params["set_{$col}"] = $val;
        }
        $conds = [];
        foreach ($where as $col => $val) {
            $conds[] = "`{$col}` = :where_{$col}";
            $params["where_{$col}"] = $val;
        }
        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $set),
            implode(' AND ', $conds)
        );
        return $this->run($sql, $params)->rowCount();
    }

    /**
     * Delete rows matching a where clause.
     *
     * @param array<string,mixed> $where
     */
    public function delete(string $table, array $where): int
    {
        $conds = [];
        foreach ($where as $col => $val) {
            $conds[] = "`{$col}` = :{$col}";
        }
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $table, implode(' AND ', $conds));
        return $this->run($sql, $where)->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
