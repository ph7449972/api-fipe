<?php
namespace App\Infrastructure\Http;

use GuzzleHttp\Client;

class FipeClient
{
    private Client $http;

    public function __construct(private string $baseUrl)
    {
        $this->http = new Client(['base_uri' => $this->baseUrl, 'timeout' => 30]);
    }

    public function getModels(string $brandCode): array
    {
        $res = $this->http->get('/fipe/api/v1/carros/marcas/' . rawurlencode($brandCode) . '/modelos');
        $data = json_decode((string)$res->getBody(), true) ?? [];
        // API returns { modelos: [...], anos: [...] }
        return $data['modelos'] ?? [];
    }
}
