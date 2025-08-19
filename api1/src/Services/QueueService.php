<?php
namespace Fipe\Api1\Services;

use Predis\Client;

class QueueService
{
    public function __construct(private Client $redis) {}

    public function enqueue(string $queue, array $message): void
    {
        $this->redis->rpush($queue, [json_encode($message, JSON_UNESCAPED_UNICODE)]);
    }
}
