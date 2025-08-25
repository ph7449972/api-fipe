<?php
namespace App\Infrastructure\Queue;

use Predis\Client as RedisClient;

class QueuePublisher
{
    private RedisClient $redis;

    public function __construct(string $host, int $port, private string $queueKey)
    {
        $this->redis = new RedisClient(['host' => $host, 'port' => $port]);
    }

    public function publish(array $brand): void
    {
        $this->redis->lpush($this->queueKey, [json_encode($brand, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }
}
