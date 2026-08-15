<?php

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    protected string $table = 'audit_logs';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function recent(int $limit = 50): array
    {
        $limit = max(1, $limit);
        return $this->db()->fetchAll(
            "SELECT * FROM `audit_logs` ORDER BY id DESC LIMIT {$limit}"
        );
    }
}
