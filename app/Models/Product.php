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

        // 1. If string contains a pipe "|" (WHMCS Dropdown raw value format, e.g. "KEY|Label")
        if (strpos($search, '|') !== false) {
            $parts = explode('|', $search);
            $first = trim($parts[0]);
            $second = trim($parts[1] ?? '');

            if ($first !== '' && $first !== '0') {
                $p = $this->findByNameOrKey($first);
                if ($p !== null) {
                    return $p;
                }
            }
            if ($second !== '') {
                $p = $this->findByNameOrKey($second);
                if ($p !== null) {
                    return $p;
                }
            }
        }

        // 2. If string contains parentheses e.g. "Name (KEY)" or "Batch Delete Clients (BATCH-DELETE-CLIENTS)"
        if (preg_match('/^(.*?)\s*\(([^)]+)\)$/', $search, $matches)) {
            $namePart = trim($matches[1]);
            $keyPart  = trim($matches[2]);

            if ($keyPart !== '') {
                $p = $this->findByNameOrKey($keyPart);
                if ($p !== null) {
                    return $p;
                }
            }
            if ($namePart !== '') {
                $p = $this->findByNameOrKey($namePart);
                if ($p !== null) {
                    return $p;
                }
            }
        }

        // 3. Direct key match
        $byKey = $this->findBy(['product_key' => $search]);
        if ($byKey !== null) {
            return $byKey;
        }

        // 4. Direct name match
        $byName = $this->findBy(['product_name' => $search]);
        if ($byName !== null) {
            return $byName;
        }

        // 5. Case-insensitive / LIKE search across key and name
        $row = $this->db()->fetch(
            "SELECT * FROM `{$this->table}` 
             WHERE LOWER(`product_key`) = LOWER(:q1) 
                OR LOWER(`product_name`) = LOWER(:q2) 
                OR LOWER(REPLACE(`product_key`, '-', '_')) = LOWER(REPLACE(:q3, '-', '_'))
                OR LOWER(REPLACE(`product_name`, ' ', '-')) = LOWER(REPLACE(:q4, ' ', '-'))
                OR `product_name` LIKE :q5 
                OR `product_key` LIKE :q6
             ORDER BY (`status` = 'active') DESC, `id` ASC LIMIT 1",
            [
                'q1' => $search,
                'q2' => $search,
                'q3' => $search,
                'q4' => $search,
                'q5' => '%' . $search . '%',
                'q6' => '%' . $search . '%',
            ]
        );

        return $row ?: null;
    }

    /**
     * Smart resolver: resolves product by ID, Key, Name, or fallbacks.
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

        if (is_string($identifier) && trim($identifier) !== '' && $identifier !== '0') {
            $product = $this->findByNameOrKey($identifier);
            if ($product !== null) {
                return $product;
            }
            return null;
        }

        // Fallback only if no identifier was passed ($identifier is null, empty or '0')
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
