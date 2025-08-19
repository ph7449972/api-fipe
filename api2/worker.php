<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Predis\Client as RedisClient;
use Illuminate\Database\Capsule\Manager as Capsule;
use GuzzleHttp\Client as Http;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$queueName = $_ENV['QUEUE_BRANDS'] ?? 'fipe:brands';

$redis = new RedisClient([
    'scheme' => 'tcp',
    'host' => $_ENV['REDIS_HOST'] ?? 'redis',
    'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
]);

$db = new Capsule();
$db->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'mysql',
    'database'  => $_ENV['DB_NAME'] ?? 'fipe',
    'username'  => $_ENV['DB_USER'] ?? 'fipe',
    'password'  => $_ENV['DB_PASS'] ?? 'secret',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);
$db->setAsGlobal();
$db->bootEloquent();

$http = new Http([
    'base_uri' => 'https://parallelum.com.br/fipe/api/v1/',
    'timeout' => 20.0,
]);

echo "Worker started, listening on {$queueName}\n";

while (true) {
    try {
        $item = $redis->blpop([$queueName], 10);
        if (!$item) {
            continue; // timeout, loop again
        }
        [$queue, $payload] = $item;
        $msg = json_decode($payload, true);
        if (!is_array($msg)) {
            echo "Invalid message: $payload\n";
            continue;
        }
        $type = $msg['type'] ?? null; // carros|motos|caminhoes
        $brandCode = $msg['code'] ?? null;
        $brandName = $msg['name'] ?? '';
        if (!$type || !$brandCode) {
            echo "Missing type/code in message: $payload\n";
            continue;
        }

        // Upsert brand
        $brandId = upsertBrand($type, $brandCode, $brandName);

        // Fetch models for brand
        $resp = $http->get("{$type}/marcas/{$brandCode}/modelos");
        $data = json_decode((string)$resp->getBody(), true);
        $models = $data['modelos'] ?? [];

        $count = 0;
        foreach ($models as $m) {
            $code = (string)($m['codigo'] ?? $m['code'] ?? '');
            $model = (string)($m['nome'] ?? $m['name'] ?? '');
            if ($code === '') continue;
            upsertVehicle($brandId, $code, $model);
            $count++;
        }
        echo "Processed brand {$brandName} ({$type}:{$brandCode}) => {$count} models\n";
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
        sleep(1);
    }
}

function upsertBrand(string $type, string $code, string $name): int {
    $existing = Capsule::table('brands')->where(['type' => $type, 'code' => $code])->first();
    if ($existing) {
        if ($name && $existing->name !== $name) {
            Capsule::table('brands')->where('id', $existing->id)->update(['name' => $name]);
        }
        return (int)$existing->id;
    }
    $id = Capsule::table('brands')->insertGetId([
        'type' => $type,
        'code' => $code,
        'name' => $name,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    return (int)$id;
}

function upsertVehicle(int $brandId, string $code, string $model): void {
    $existing = Capsule::table('vehicles')->where(['brand_id' => $brandId, 'code' => $code])->first();
    if ($existing) {
        if ($model && $existing->model !== $model) {
            Capsule::table('vehicles')->where('id', $existing->id)->update([
                'model' => $model,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return;
    }
    Capsule::table('vehicles')->insert([
        'brand_id' => $brandId,
        'code' => $code,
        'model' => $model,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}
