<?php
namespace App\Controllers;

use App\Services\SeedService;

class SeedController
{
    public function __construct(private SeedService $service) {}

    public function seed(): array
    {
        $count = $this->service->enqueueBrands();
        return ['message' => 'Brands enqueued', 'count' => $count];
    }
}
