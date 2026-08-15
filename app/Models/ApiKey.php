<?php

namespace App\Models;

use App\Core\Model;

class ApiKey extends Model
{
    protected string $table = 'api_keys';

    public function findByKey(string $apiKey): ?array
    {
        return $this->findBy(['api_key' => $apiKey]);
    }

    public function findActiveByKey(string $apiKey): ?array
    {
        return $this->db()->fetch(
            "SELECT * FROM `api_keys` WHERE api_key = :k AND status = 'active' LIMIT 1",
            ['k' => $apiKey]
        );
    }

    public function touchUsed(int $id): void
    {
        $this->updateById($id, ['last_used_at' => date('Y-m-d H:i:s')]);
    }
}
