<?php
namespace App\Controllers;

use App\Services\UpdateService;

class UpdateController
{
    public function __construct(private UpdateService $service) {}

    public function updateVehicle(int $id): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $model = $input['modelo'] ?? null;
        $notes = $input['observacoes'] ?? null;
        if ($model === null && $notes === null) {
            http_response_code(400);
            return ['error' => 'At least one of modelo or observacoes must be provided'];
        }
        $ok = $this->service->updateVehicle($id, $model, $notes);
        return ['updated' => $ok];
    }
}
