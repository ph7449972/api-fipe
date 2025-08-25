<?php
namespace App\Controllers;

use App\Services\QueryService;

class QueryController
{
    public function __construct(private QueryService $service) {}

    public function brands(): array
    {
        return $this->service->getBrands();
    }

    public function vehiclesByBrand(): array
    {
        $brandCode = $_GET['marca_codigo'] ?? null;
        if (!$brandCode) {
            http_response_code(400);
            return ['error' => 'marca_codigo is required'];
        }
        return $this->service->getVehiclesByBrand((string)$brandCode);
    }
}
