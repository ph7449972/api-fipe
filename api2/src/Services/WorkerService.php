<?php
namespace App\Services;

use App\Infrastructure\Http\FipeClient;
use App\Infrastructure\Queue\QueueConsumer;
use App\Infrastructure\Repositories\BrandRepository;
use App\Infrastructure\Repositories\ModelRepository;

class WorkerService
{
    public function __construct(
        private QueueConsumer $queue,
        private FipeClient $fipe,
        private BrandRepository $brands,
        private ModelRepository $models
    ) {}

    public function run(): void
    {
        while (true) {
            $brand = $this->queue->popBlocking(5);
            if (!$brand) {
                continue; // idle
            }
            $brandCode = (string)($brand['codigo'] ?? $brand['code'] ?? '');
            $brandName = (string)($brand['nome'] ?? $brand['name'] ?? '');
            if ($brandCode === '') {
                continue;
            }
            $brandId = $this->brands->upsert($brandCode, $brandName);
            $models = $this->fipe->getModels($brandCode);
            foreach ($models as $m) {
                $code = (string)($m['codigo'] ?? $m['code'] ?? '');
                $name = (string)($m['nome'] ?? $m['name'] ?? '');
                if ($code === '') continue;
                $this->models->upsert($brandId, $code, $name);
            }
        }
    }
}
