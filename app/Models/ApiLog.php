<?php

namespace App\Models;

use App\Core\Model;

class ApiLog extends Model
{
    protected string $table = 'api_logs';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function paginate(int $limit, int $offset, ?bool $onlyFailed = null): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        $where  = '1=1';
        $params = [];
        if ($onlyFailed === true) {
            $where = 'success = 0';
        }
        return $this->db()->fetchAll(
            "SELECT * FROM `api_logs` WHERE {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function totalCount(?bool $onlyFailed = null): int
    {
        if ($onlyFailed === true) {
            return (int) $this->db()->scalar('SELECT COUNT(*) FROM `api_logs` WHERE success = 0');
        }
        return (int) $this->db()->scalar('SELECT COUNT(*) FROM `api_logs`');
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
}
