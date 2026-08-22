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
     * Find existing active license for the specified domain & product.
     *
     * @return array<string,mixed>|null
     */
    public function findActiveByDomainAndProduct(string $domain, int $productId, int $ignoreId = 0): ?array
    {
        $domain = trim($domain);
        if ($domain === '' || $domain === '*') {
            return null;
        }

        return $this->db()->fetch(
            "SELECT * FROM `licenses`
             WHERE `product_id` = :pid
               AND `status` = 'active'
               AND `id` != :ignore_id
               AND (
                   LOWER(`domain`) = LOWER(:d1)
                   OR LOWER(`domain`) = LOWER(:d2)
                   OR LOWER(`domain`) = LOWER(:d3)
               )
             ORDER BY `id` DESC
             LIMIT 1",
            [
                'pid'       => $productId,
                'ignore_id' => $ignoreId,
                'd1'        => $domain,
                'd2'        => 'www.' . preg_replace('/^www\./i', '', $domain),
                'd3'        => preg_replace('/^www\./i', '', $domain),
            ]
        );
    }

    /**
     * Find license by WHMCS Service ID.
     *
     * @return array<string,mixed>|null
     */
    public function findByWhmcsService(int $serviceId): ?array
    {
        if ($serviceId <= 0) {
            return null;
        }
        return $this->findBy(['whmcs_service_id' => $serviceId]);
    }

    /**
     * Lock a single license row for the duration of the current transaction
     * (SELECT ... FOR UPDATE). Used by LicenseService to serialise
     * activation-limit checks and prevent races under concurrency.
     *
     * @return array<string,mixed>|null
     */
    public function lockForUpdate(int $id): ?array
    {
        return $this->db()->fetch(
            "SELECT * FROM `licenses` WHERE `id` = :id FOR UPDATE",
            ['id' => $id]
        );
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
