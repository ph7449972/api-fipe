<?php
namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;
use PDO;

class BrandRepository
{
    public function __construct(private Connection $conn) {}

    public function upsert(string $code, string $name): int
    {
        $sql = 'INSERT INTO brands(code, name) VALUES(:code, :name)
                ON DUPLICATE KEY UPDATE name = VALUES(name), id = LAST_INSERT_ID(id)';
        $stmt = $this->conn->pdo()->prepare($sql);
        $stmt->execute([':code' => $code, ':name' => $name]);
        return (int)$this->conn->pdo()->lastInsertId();
    }
}
