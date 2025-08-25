<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Container;
use App\Core\AuthMiddleware;
use App\Controllers\SeedController;
use App\Controllers\QueryController;
use App\Controllers\UpdateController;

// Load env from docker env vars
$container = new Container();

$router = new Router();

// Middleware: Basic Auth
$auth = new AuthMiddleware(
    getenv('APP_USER') ?: 'admin',
    getenv('APP_PASS') ?: 'admin123'
);

// Routes
$router->add('POST', '/v1/seed', [SeedController::class, 'seed'], [$auth]);
$router->add('GET', '/v1/marcas', [QueryController::class, 'brands'], [$auth]);
$router->add('GET', '/v1/veiculos', [QueryController::class, 'vehiclesByBrand'], [$auth]);
$router->add('PUT', '/v1/veiculos/(\\d+)', [UpdateController::class, 'updateVehicle'], [$auth]);

// Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $container);
