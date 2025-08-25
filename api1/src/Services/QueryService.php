<?php
namespace App\Services;

use App\Infrastructure\Repositories\BrandRepository;
use App\Infrastructure\Repositories\VehicleRepository;
use App\Infrastructure\Cache\Cache;

class QueryService
{
    public function __construct(
        private BrandRepository $brands,
        private VehicleRepository $vehicles,
        private Cache $cache
    ) {}

    public function getBrands(): array
    {
        return $this->brands->all();
    }

    public function getVehiclesByBrand(string $brandCode): array
    {
        $cacheKey = "vehicles:brand:" . $brandCode;
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }
        $data = $this->vehicles->byBrandCode($brandCode);
        $this->cache->set($cacheKey, $data);
        return $data;
    }
}
