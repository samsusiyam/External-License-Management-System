<?php

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';

    public function findByKey(string $productKey): ?array
    {
        return $this->findBy(['product_key' => $productKey]);
    }

    public function keyExists(string $productKey, int $ignoreId = 0): bool
    {
        $row = $this->findBy(['product_key' => $productKey]);
        if ($row === null) {
            return false;
        }
        return (int) $row['id'] !== $ignoreId;
    }
}
