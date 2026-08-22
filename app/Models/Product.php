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

    public function findFirstActive(): ?array
    {
        return $this->findBy(['status' => 'active']);
    }

    public function findByNameOrKey(string $search): ?array
    {
        $search = trim($search);
        if ($search === '') {
            return null;
        }

        // Direct key match
        $byKey = $this->findBy(['product_key' => $search]);
        if ($byKey !== null) {
            return $byKey;
        }

        // Direct name match
        $byName = $this->findBy(['product_name' => $search]);
        if ($byName !== null) {
            return $byName;
        }

        // Case-insensitive / LIKE search
        $row = $this->db()->fetch(
            "SELECT * FROM `{$this->table}` 
             WHERE LOWER(`product_key`) = LOWER(:q1) 
                OR LOWER(`product_name`) = LOWER(:q2) 
                OR `product_name` LIKE :q3 
             ORDER BY (`status` = 'active') DESC, `id` ASC LIMIT 1",
            [
                'q1' => $search,
                'q2' => $search,
                'q3' => '%' . $search . '%',
            ]
        );

        return $row ?: null;
    }

    /**
     * Smart resolver: resolves product by ID, Key, Name, or fallbacks.
     * Always returns an active product if one exists, or creates a default one.
     *
     * @param mixed $identifier
     * @return array<string,mixed>|null
     */
    public function resolveSmart(mixed $identifier = null): ?array
    {
        if (is_numeric($identifier) && (int) $identifier > 0) {
            $product = $this->find((int) $identifier);
            if ($product !== null) {
                return $product;
            }
        }

        if (is_string($identifier) && trim($identifier) !== '') {
            $product = $this->findByNameOrKey($identifier);
            if ($product !== null) {
                return $product;
            }
        }

        // Fallback: first active product
        $active = $this->findFirstActive();
        if ($active !== null) {
            return $active;
        }

        // If no active product exists at all, auto-create a default product
        $count = $this->count();
        if ($count === 0) {
            $id = $this->create([
                'product_name'   => 'Default Software',
                'product_key'    => 'default-software',
                'description'    => 'Auto-created default product for license management',
                'latest_version' => '1.0.0',
                'status'         => 'active',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            return $this->find($id);
        }

        return $this->first();
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
