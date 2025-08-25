<?php
namespace App\Infrastructure\Queue;

use Predis\Client as RedisClient;

class QueueConsumer
{
    private RedisClient $redis;

    public function __construct(string $host, int $port, private string $queueKey)
    {
        $this->redis = new RedisClient(['host' => $host, 'port' => $port]);
    }

    public function popBlocking(int $timeout = 5): ?array
    {
        $res = $this->redis->brpop([$this->queueKey], $timeout);
        if (!$res) return null;
        [, $payload] = $res;
        return json_decode($payload, true) ?? null;
    }
}
