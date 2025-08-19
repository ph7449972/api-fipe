<?php
use Slim\App;
use Slim\Psr7\Response;
use Fipe\Api1\Services\FipeService;
use Fipe\Api1\Services\CacheService;
use Fipe\Api1\Services\QueueService;
use Fipe\Api1\Repositories\BrandRepository;
use Fipe\Api1\Repositories\VehicleRepository;
use function Fipe\Api1\authMiddleware;
use function Fipe\Api1\stream_for_json;

return function (App $app) {
    authMiddleware($app);

    $app->get('/health', function() {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok']));
    });

    $app->post('/load-initial', function($request) {
        $fipe = new FipeService();
        $queue = new QueueService($GLOBALS['redis']());
        $types = ['carros','motos','caminhoes'];
        $enqueued = 0;
        foreach ($types as $type) {
            $brands = $fipe->getBrands($type);
            foreach ($brands as $b) {
                $queue->enqueue($GLOBALS['queue_name'], [
                    'type' => $type,
                    'code' => (string)($b['codigo'] ?? $b['code'] ?? ''),
                    'name' => (string)($b['nome'] ?? $b['name'] ?? ''),
                ]);
                $enqueued++;
            }
        }
        $res = new Response();
        $res->getBody()->write(json_encode(['enqueued' => $enqueued]));
        return $res->withHeader('Content-Type', 'application/json');
    });

    $app->get('/brands', function($request) {
        $cache = new CacheService($GLOBALS['redis']());
        $repo = new BrandRepository();
        $cacheKey = 'brands:all';
        $data = $cache->getJson($cacheKey);
        if ($data === null) {
            $data = $repo->all();
            $cache->setJson($cacheKey, $data, 300);
        }
        $res = new Response();
        $res->getBody()->write(json_encode($data));
        return $res->withHeader('Content-Type', 'application/json');
    });

    $app->get('/vehicles', function($request) {
        $params = $request->getQueryParams();
        $brandId = $params['brand_id'] ?? null;
        $brand = $params['brand'] ?? null;
        $repoB = new BrandRepository();
        if (!$brandId && $brand) {
            $brandRow = $repoB->findByName($brand);
            $brandId = $brandRow['id'] ?? null;
        }
        if (!$brandId) {
            $res = new Response(400);
            $res->getBody()->write(json_encode(['error' => 'brand_id or brand is required']));
            return $res->withHeader('Content-Type', 'application/json');
        }
        $cache = new CacheService($GLOBALS['redis']());
        $repo = new VehicleRepository();
        $cacheKey = 'vehicles:brand:' . $brandId;
        $data = $cache->getJson($cacheKey);
        if ($data === null) {
            $data = $repo->byBrand((int)$brandId);
            $cache->setJson($cacheKey, $data, 300);
        }
        $res = new Response();
        $res->getBody()->write(json_encode($data));
        return $res->withHeader('Content-Type', 'application/json');
    });

    $app->put('/vehicles/{code}', function($request, $response, $args) {
        $code = $args['code'];
        $payload = (array)$request->getParsedBody();
        $model = $payload['model'] ?? null;
        $observations = $payload['observations'] ?? null;
        if ($model === null && $observations === null) {
            $res = new Response(400);
            $res->getBody()->write(json_encode(['error' => 'Nothing to update']));
            return $res->withHeader('Content-Type', 'application/json');
        }
        $repo = new VehicleRepository();
        $updated = $repo->updateByCode($code, $model, $observations);
        // Invalidate caches related to this vehicle's brand
        if ($updated && isset($updated['brand_id'])) {
            $cache = new CacheService($GLOBALS['redis']());
            $cache->del('vehicles:brand:' . $updated['brand_id']);
        }
        $res = new Response();
        $res->getBody()->write(json_encode(['updated' => (bool)$updated]));
        return $res->withHeader('Content-Type', 'application/json');
    });
};
