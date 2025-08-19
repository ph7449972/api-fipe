<?php
namespace Fipe\Api1\Services;

use Predis\Client;

class CacheService
{
    public function __construct(private Client $redis) {}

    public function getJson(string $key): ?array
    {
        $val = $this->redis->get($key);
        return $val ? json_decode($val, true) : null;
    }

    public function setJson(string $key, array $payload, int $ttl): void
    {
        $this->redis->setex($key, $ttl, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function del(string $key): void
    {
        $this->redis->del([$key]);
    }
}
