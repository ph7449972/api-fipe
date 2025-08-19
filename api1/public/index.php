<?php
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Base container setup: DB, Redis, Cache, Queue, Auth
require __DIR__ . '/../src/bootstrap.php';

// Routes
(require __DIR__ . '/../src/routes.php')($app);

$app->run();
