<?php
namespace Fipe\Api1\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class BrandRepository
{
    public function all(): array
    {
        return DB::table('brands')->select('id', 'type', 'code', 'name')->orderBy('type')->orderBy('name')->get()->map(function($r){
            return (array)$r;
        })->toArray();
    }

    public function findByName(string $name): ?array
    {
        $row = DB::table('brands')->where('name', $name)->first();
        return $row ? (array)$row : null;
    }
}
