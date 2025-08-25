<?php
namespace App\Infrastructure\Database;

use PDO;

class Connection
{
    private PDO $pdo;

    public function __construct(string $host, int $port, string $db, string $user, string $pass)
    {
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        $attempts = 0; $max = 30; $waitMs = 500;
        while (true) {
            try {
                $this->pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                break;
            } catch (\PDOException $e) {
                if (++$attempts >= $max) { throw $e; }
                usleep($waitMs * 1000);
            }
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
