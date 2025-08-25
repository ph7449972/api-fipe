<?php
namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;
use PDO;

class VehicleRepository
{
    public function __construct(private Connection $conn) {}

    public function byBrandCode(string $brandCode): array
    {
        $sql = 'SELECT m.id, m.code, b.code as brand_code, b.name as marca, m.name as modelo, m.notes as observacoes
                FROM models m
                JOIN brands b ON b.id = m.brand_id
                WHERE b.code = :code
                ORDER BY m.name';
        $stmt = $this->conn->pdo()->prepare($sql);
        $stmt->execute([':code' => $brandCode]);
        return $stmt->fetchAll();
    }

    public function update(int $id, ?string $model, ?string $notes): bool
    {
        $fields = [];
        $params = [':id' => $id];
        if ($model !== null) { $fields[] = 'name = :name'; $params[':name'] = $model; }
        if ($notes !== null) { $fields[] = 'notes = :notes'; $params[':notes'] = $notes; }
        if (!$fields) return false;
        $sql = 'UPDATE models SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $this->conn->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function getBrandCodeByVehicleId(int $id): ?string
    {
        $stmt = $this->conn->pdo()->prepare('SELECT b.code FROM models m JOIN brands b ON b.id = m.brand_id WHERE m.id = :id');
        $stmt->execute([':id' => $id]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : null;
    }
}
