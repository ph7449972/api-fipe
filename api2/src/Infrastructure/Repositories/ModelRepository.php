<?php
namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;

class ModelRepository
{
    public function __construct(private Connection $conn) {}

    public function upsert(int $brandId, string $code, string $name): int
    {
        $sql = 'INSERT INTO models(brand_id, code, name) VALUES(:brand_id, :code, :name)
                ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = CURRENT_TIMESTAMP, id = LAST_INSERT_ID(id)';
        $stmt = $this->conn->pdo()->prepare($sql);
        $stmt->execute([':brand_id' => $brandId, ':code' => $code, ':name' => $name]);
        return (int)$this->conn->pdo()->lastInsertId();
    }
}
