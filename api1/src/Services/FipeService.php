<?php
namespace Fipe\Api1\Services;

use GuzzleHttp\Client;

class FipeService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => 'https://parallelum.com.br/fipe/api/v1/',
            'timeout' => 20.0,
        ]);
    }

    public function getBrands(string $type): array
    {
        // types: carros, motos, caminhoes
        $resp = $this->http->get($type . '/marcas');
        $data = json_decode((string)$resp->getBody(), true);
        return is_array($data) ? $data : [];
    }
}
