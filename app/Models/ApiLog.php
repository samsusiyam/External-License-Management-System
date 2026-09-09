<?php

namespace App\Models;

use App\Core\Model;

class ApiLog extends Model
{
    protected string $table = 'api_logs';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function paginate(
        int $limit,
        int $offset,
        ?bool $onlyFailed = null,
        ?string $search = null,
        ?string $method = null
    ): array {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        [$where, $params] = $this->buildFilterWhere($onlyFailed, $search, $method);

        return $this->db()->fetchAll(
            "SELECT * FROM `api_logs` WHERE {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function totalCount(
        ?bool $onlyFailed = null,
        ?string $search = null,
        ?string $method = null
    ): int {
        [$where, $params] = $this->buildFilterWhere($onlyFailed, $search, $method);
        return (int) $this->db()->scalar("SELECT COUNT(*) FROM `api_logs` WHERE {$where}", $params);
    }

    /**
     * Delete multiple logs by IDs.
     *
     * @param array<int> $ids
     */
    public function deleteByIds(array $ids): int
    {
        $cleanIds = array_values(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0));
        if (empty($cleanIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $stmt = $this->db()->run("DELETE FROM `api_logs` WHERE id IN ({$placeholders})", $cleanIds);
        return $stmt->rowCount();
    }

    /**
     * Delete logs older than N days.
     */
    public function deleteOlderThan(int $days): int
    {
        $days = max(1, $days);
        $stmt = $this->db()->run(
            "DELETE FROM `api_logs` WHERE created_at < (NOW() - INTERVAL :days DAY)",
            ['days' => $days]
        );
        return $stmt->rowCount();
    }

    /**
     * Clear all logs or only failed logs.
     */
    public function clearAll(?bool $onlyFailed = null): int
    {
        if ($onlyFailed === true) {
            $stmt = $this->db()->run("DELETE FROM `api_logs` WHERE success = 0");
            return $stmt->rowCount();
        }
        $stmt = $this->db()->run("DELETE FROM `api_logs`");
        return $stmt->rowCount();
    }

    /**
     * Count requests within the last N hours.
     */
    public function countSince(int $hours, ?bool $onlyFailed = null): int
    {
        $where = 'created_at >= (NOW() - INTERVAL :h HOUR)';
        if ($onlyFailed === true) {
            $where .= ' AND success = 0';
        }
        return (int) $this->db()->scalar(
            "SELECT COUNT(*) FROM `api_logs` WHERE {$where}",
            ['h' => $hours]
        );
    }

    /**
     * @return array{0:string, 1:array<string,mixed>}
     */
    private function buildFilterWhere(?bool $onlyFailed, ?string $search, ?string $method): array
    {
        $conds = [];
        $params = [];

        if ($onlyFailed === true) {
            $conds[] = 'success = 0';
        }

        if ($method !== null && $method !== '') {
            $conds[] = 'method = :method';
            $params['method'] = strtoupper($method);
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $conds[] = '(endpoint LIKE :s_ep OR ip LIKE :s_ip OR response_body LIKE :s_res OR request_body LIKE :s_req OR status_code LIKE :s_code)';
            $params['s_ep']   = $term;
            $params['s_ip']   = $term;
            $params['s_res']  = $term;
            $params['s_req']  = $term;
            $params['s_code'] = $term;
        }

        $where = empty($conds) ? '1=1' : implode(' AND ', $conds);
        return [$where, $params];
    }
}
