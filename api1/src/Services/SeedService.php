<?php
namespace App\Services;

use App\Infrastructure\Http\FipeClient;
use App\Infrastructure\Queue\QueuePublisher;

class SeedService
{
    public function __construct(private FipeClient $fipe, private QueuePublisher $queue) {}

    public function enqueueBrands(): int
    {
        $brands = $this->fipe->getBrands();
        $count = 0;
        foreach ($brands as $brand) {
            $this->queue->publish($brand);
            $count++;
        }
        return $count;
    }
}
