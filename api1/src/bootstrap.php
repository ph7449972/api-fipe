<?php
namespace Fipe\Api1;

use Illuminate\Database\Capsule\Manager as Capsule;
use Predis\Client as RedisClient;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\App;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Container-like globals (simple, for brevity)
$GLOBALS['db'] = function() {
    static $capsule = null;
    if ($capsule) return $capsule;
    $capsule = new Capsule();
    $capsule->addConnection([
        'driver'    => 'mysql',
        'host'      => $_ENV['DB_HOST'] ?? 'mysql',
        'database'  => $_ENV['DB_NAME'] ?? 'fipe',
        'username'  => $_ENV['DB_USER'] ?? 'fipe',
        'password'  => $_ENV['DB_PASS'] ?? 'secret',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    return $capsule;
};

$GLOBALS['redis'] = function() {
    static $client = null;
    if ($client) return $client;
    $client = new RedisClient([
        'scheme' => 'tcp',
        'host' => $_ENV['REDIS_HOST'] ?? 'redis',
        'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
    ]);
    return $client;
};

$GLOBALS['queue_name'] = $_ENV['QUEUE_BRANDS'] ?? 'fipe:brands';
$GLOBALS['jwt_secret'] = $_ENV['JWT_SECRET'] ?? 'devsecret';

function authMiddleware(App $app): void {
    $app->add(function (Request $request, RequestHandler $handler) {
        $path = $request->getUri()->getPath();
        if ($path === '/health') {
            return $handler->handle($request);
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.*)$/i', $authHeader, $m)) {
            return (new \Slim\Psr7\Response(401))->withHeader('Content-Type', 'application/json')
                ->withBody(stream_for_json(['error' => 'Unauthorized']));
        }
        $token = $m[1];
        try {
            JWT::decode($token, new Key($GLOBALS['jwt_secret'], 'HS256'));
        } catch (\Throwable $e) {
            return (new \Slim\Psr7\Response(401))->withHeader('Content-Type', 'application/json')
                ->withBody(stream_for_json(['error' => 'Invalid token']));
        }
        return $handler->handle($request);
    });
}

function stream_for_json(array $payload): \Psr\Http\Message\StreamInterface {
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $body);
    rewind($stream);
    return new \Slim\Psr7\Stream($stream);
}
