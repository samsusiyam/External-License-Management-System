<?php

namespace App\Models;

use App\Core\Model;

class AdminUser extends Model
{
    protected string $table = 'admin_users';

    public function findByUsername(string $username): ?array
    {
        return $this->findBy(['username' => $username]);
    }

    public function touchLogin(int $id): void
    {
        $this->updateById($id, ['last_login_at' => date('Y-m-d H:i:s')]);
    }
}
