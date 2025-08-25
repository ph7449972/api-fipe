<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\FipeClient;
use App\Infrastructure\Queue\QueueConsumer;
use App\Infrastructure\Repositories\BrandRepository;
use App\Infrastructure\Repositories\ModelRepository;
use App\Services\WorkerService;

function runMigrations(Connection $conn): void {
    $pdo = $conn->pdo();
    $pdo->exec('CREATE TABLE IF NOT EXISTS brands (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        code VARCHAR(50) NOT NULL,
        name VARCHAR(255) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_brand_code (code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $pdo->exec('CREATE TABLE IF NOT EXISTS models (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        brand_id INT UNSIGNED NOT NULL,
        code VARCHAR(50) NOT NULL,
        name VARCHAR(255) NOT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_brand_model (brand_id, code),
        KEY idx_models_brand_id (brand_id),
        CONSTRAINT fk_models_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

$host = getenv('DB_HOST') ?: 'mysql';
$port = (int)(getenv('DB_PORT') ?: 3306);
// Se o host for o serviço docker 'mysql', force a porta interna 3306
if ($host === 'mysql') { $port = 3306; }
$db = getenv('DB_NAME') ?: 'fipe';
$user = getenv('DB_USER') ?: 'fipe';
$pass = getenv('DB_PASS') ?: 'fipe';

$conn = new Connection($host, $port, $db, $user, $pass);
runMigrations($conn);

$queue = new QueueConsumer(getenv('REDIS_HOST') ?: 'redis', (int)(getenv('REDIS_PORT') ?: 6379), getenv('QUEUE_BRANDS_KEY') ?: 'brands_queue');
$fipe = new FipeClient(getenv('FIPE_BASE_URL') ?: 'https://parallelum.com.br/fipe/api/v1/carros');
$brands = new BrandRepository($conn);
$models = new ModelRepository($conn);

$service = new WorkerService($queue, $fipe, $brands, $models);
$service->run();
