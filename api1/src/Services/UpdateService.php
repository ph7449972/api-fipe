<?php
namespace App\Services;

use App\Infrastructure\Repositories\VehicleRepository;
use App\Infrastructure\Cache\Cache;

class UpdateService
{
    public function __construct(private VehicleRepository $vehicles, private Cache $cache) {}

    public function updateVehicle(int $id, ?string $model, ?string $notes): bool
    {
        $ok = $this->vehicles->update($id, $model, $notes);
        if ($ok) {
            // Invalidate caches touching vehicles
            $brandCode = $this->vehicles->getBrandCodeByVehicleId($id);
            if ($brandCode) {
                $this->cache->delete("vehicles:brand:" . $brandCode);
            }
        }
        return $ok;
    }
}
