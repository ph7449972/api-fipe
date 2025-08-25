<?php
namespace App\Core;

use App\Services\SeedService;
use App\Services\QueryService;
use App\Services\UpdateService;
use App\Infrastructure\Http\FipeClient;
use App\Infrastructure\Queue\QueuePublisher;
use App\Infrastructure\Cache\Cache;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repositories\BrandRepository;
use App\Infrastructure\Repositories\VehicleRepository;

class Container
{
    private array $instances = [];

    public function get(string $class)
    {
        if (!isset($this->instances[$class])) {
            $this->instances[$class] = $this->build($class);
        }
        return $this->instances[$class];
    }

    private function build(string $class)
    {
        return match ($class) {
            // Controllers
            \App\Controllers\SeedController::class => new \App\Controllers\SeedController($this->get(SeedService::class)),
            \App\Controllers\QueryController::class => new \App\Controllers\QueryController($this->get(QueryService::class)),
            \App\Controllers\UpdateController::class => new \App\Controllers\UpdateController($this->get(UpdateService::class)),

            // Services
            SeedService::class => new SeedService($this->get(FipeClient::class), $this->get(QueuePublisher::class)),
            QueryService::class => new QueryService($this->get(BrandRepository::class), $this->get(VehicleRepository::class), $this->get(Cache::class)),
            UpdateService::class => new UpdateService($this->get(VehicleRepository::class), $this->get(Cache::class)),

            // Infrastructure
            FipeClient::class => new FipeClient(getenv('FIPE_BASE_URL') ?: 'https://parallelum.com.br/fipe/api/v1/carros'),
            QueuePublisher::class => new QueuePublisher(getenv('REDIS_HOST') ?: 'redis', (int)(getenv('REDIS_PORT') ?: 6379), getenv('QUEUE_BRANDS_KEY') ?: 'brands_queue'),
            Cache::class => new Cache(getenv('REDIS_HOST') ?: 'redis', (int)(getenv('REDIS_PORT') ?: 6379), (int)(getenv('REDIS_CACHE_TTL') ?: 300)),
            Connection::class => new Connection(
                host: getenv('DB_HOST') ?: 'postgres',
                port: (int)(getenv('DB_PORT') ?: 5432),
                db: getenv('DB_NAME') ?: 'fipe',
                user: getenv('DB_USER') ?: 'fipe',
                pass: getenv('DB_PASS') ?: 'fipe'
            ),
            BrandRepository::class => new BrandRepository($this->get(Connection::class)),
            VehicleRepository::class => new VehicleRepository($this->get(Connection::class)),

            default => new $class(),
        };
    }
}
