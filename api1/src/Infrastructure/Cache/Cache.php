<?php
namespace App\Infrastructure\Cache;

use Predis\Client as RedisClient;

class Cache
{
    private RedisClient $redis;

    public function __construct(string $host, int $port, private int $ttl)
    {
        $this->redis = new RedisClient(['host' => $host, 'port' => $port]);
    }

    public function get(string $key): ?array
    {
        $val = $this->redis->get($key);
        return $val ? json_decode($val, true) : null;
    }

    public function set(string $key, array $value): void
    {
        $this->redis->setex($key, $this->ttl, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function delete(string $key): void
    {
        $this->redis->del([$key]);
    }
}
