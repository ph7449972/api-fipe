<?php
namespace Fipe\Api1\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class VehicleRepository
{
    public function byBrand(int $brandId): array
    {
        return DB::table('vehicles')
            ->select('code', 'model', 'observations', 'brand_id')
            ->where('brand_id', $brandId)
            ->orderBy('model')
            ->get()->map(fn($r) => (array)$r)->toArray();
    }

    public function updateByCode(string $code, ?string $model, ?string $observations): ?array
    {
        $row = DB::table('vehicles')->where('code', $code)->first();
        if (!$row) return null;
        $update = [];
        if ($model !== null) $update['model'] = $model;
        if ($observations !== null) $update['observations'] = $observations;
        if ($update) {
            DB::table('vehicles')->where('code', $code)->update($update);
        }
        $row = DB::table('vehicles')->where('code', $code)->first();
        return $row ? (array)$row : null;
    }
}
