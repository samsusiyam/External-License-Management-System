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

    /**
     * @return array<int,array<string,mixed>>
     */
    public function paginate(
        int $limit,
        int $offset,
        ?string $actorType = null,
        ?string $search = null
    ): array {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        [$where, $params] = $this->buildFilterWhere($actorType, $search);

        return $this->db()->fetchAll(
            "SELECT * FROM `audit_logs` WHERE {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function totalCount(?string $actorType = null, ?string $search = null): int
    {
        [$where, $params] = $this->buildFilterWhere($actorType, $search);
        return (int) $this->db()->scalar("SELECT COUNT(*) FROM `audit_logs` WHERE {$where}", $params);
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
        $stmt = $this->db()->run("DELETE FROM `audit_logs` WHERE id IN ({$placeholders})", $cleanIds);
        return $stmt->rowCount();
    }

    /**
     * Delete audit logs older than N days.
     */
    public function deleteOlderThan(int $days): int
    {
        $days = max(1, $days);
        $stmt = $this->db()->run(
            "DELETE FROM `audit_logs` WHERE created_at < (NOW() - INTERVAL :days DAY)",
            ['days' => $days]
        );
        return $stmt->rowCount();
    }

    /**
     * Clear all audit logs.
     */
    public function clearAll(): int
    {
        $stmt = $this->db()->run("DELETE FROM `audit_logs`");
        return $stmt->rowCount();
    }

    /**
     * @return array{0:string, 1:array<string,mixed>}
     */
    private function buildFilterWhere(?string $actorType, ?string $search): array
    {
        $conds = [];
        $params = [];

        if ($actorType !== null && $actorType !== '') {
            $conds[] = 'actor_type = :actor_type';
            $params['actor_type'] = $actorType;
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $conds[] = '(action LIKE :s_act OR actor_id LIKE :s_aid OR entity_type LIKE :s_et OR entity_id LIKE :s_eid OR details LIKE :s_det OR ip LIKE :s_ip)';
            $params['s_act'] = $term;
            $params['s_aid'] = $term;
            $params['s_et']  = $term;
            $params['s_eid'] = $term;
            $params['s_det'] = $term;
            $params['s_ip']  = $term;
        }

        $where = empty($conds) ? '1=1' : implode(' AND ', $conds);
        return [$where, $params];
    }
}
