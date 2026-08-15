<?php

namespace App\Models;

use App\Core\Model;

class License extends Model
{
    protected string $table = 'licenses';

    public function findByKey(string $licenseKey): ?array
    {
        return $this->findBy(['license_key' => $licenseKey]);
    }

    public function keyExists(string $licenseKey): bool
    {
        return $this->findByKey($licenseKey) !== null;
    }

    /**
     * License rows joined with product info, with optional filters.
     *
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function paginate(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->filterClause($filters);
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        $sql = "SELECT l.*, p.product_name, p.product_key
                FROM `licenses` l
                LEFT JOIN `products` p ON p.id = l.product_id
                WHERE {$where}
                ORDER BY l.id DESC
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db()->fetchAll($sql, $params);
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function countFiltered(array $filters): int
    {
        [$where, $params] = $this->filterClause($filters);
        return (int) $this->db()->scalar(
            "SELECT COUNT(*) FROM `licenses` l WHERE {$where}",
            $params
        );
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function filterClause(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $conds[] = 'l.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['product_id'])) {
            $conds[] = 'l.product_id = :product_id';
            $params['product_id'] = (int) $filters['product_id'];
        }
        if (!empty($filters['search'])) {
            $conds[] = '(l.license_key LIKE :s OR l.customer_name LIKE :s OR l.customer_email LIKE :s OR l.domain LIKE :s)';
            $params['s'] = '%' . $filters['search'] . '%';
        }

        return [implode(' AND ', $conds), $params];
    }

    /**
     * Count licenses grouped by status.
     *
     * @return array<string,int>
     */
    public function statusCounts(): array
    {
        $rows = $this->db()->fetchAll('SELECT status, COUNT(*) AS c FROM `licenses` GROUP BY status');
        $out = ['active' => 0, 'suspended' => 0, 'expired' => 0, 'terminated' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
        }
        return $out;
    }

    /**
     * Mark licenses past their expiry date as expired.
     */
    public function expireOverdue(): int
    {
        return $this->db()->run(
            "UPDATE `licenses`
             SET status = 'expired'
             WHERE status = 'active'
               AND expiry_date IS NOT NULL
               AND expiry_date < CURDATE()"
        )->rowCount();
    }
}
