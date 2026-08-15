<?php

namespace App\Models;

use App\Core\Model;

class Activation extends Model
{
    protected string $table = 'activations';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function forLicense(int $licenseId): array
    {
        return $this->where(['license_id' => $licenseId], 'activated_at DESC');
    }

    /**
     * Count active (non-deactivated) activations for a license.
     */
    public function activeCount(int $licenseId): int
    {
        return (int) $this->db()->scalar(
            "SELECT COUNT(*) FROM `activations` WHERE license_id = :id AND status = 'active'",
            ['id' => $licenseId]
        );
    }

    /**
     * Find an active activation matching a license + domain.
     *
     * @return array<string,mixed>|null
     */
    public function findActive(int $licenseId, ?string $domain): ?array
    {
        return $this->db()->fetch(
            "SELECT * FROM `activations`
             WHERE license_id = :id AND status = 'active'
               AND (:domain_null IS NULL OR domain = :domain_match)
             LIMIT 1",
            ['id' => $licenseId, 'domain_null' => $domain, 'domain_match' => $domain]
        );
    }

    public function deactivateForLicense(int $licenseId): int
    {
        return $this->db()->run(
            "UPDATE `activations` SET status = 'deactivated' WHERE license_id = :id AND status = 'active'",
            ['id' => $licenseId]
        )->rowCount();
    }

    public function touchCheck(int $id): void
    {
        $this->updateById($id, ['last_check' => date('Y-m-d H:i:s')]);
    }
}
