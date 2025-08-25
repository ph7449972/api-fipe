<?php
namespace App\Core;

class AuthMiddleware
{
    public function __construct(private string $user, private string $pass) {}

    public function handle(): void
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            $this->deny();
        }
        $u = $_SERVER['PHP_AUTH_USER'] ?? '';
        $p = $_SERVER['PHP_AUTH_PW'] ?? '';
        if ($u !== $this->user || $p !== $this->pass) {
            $this->deny();
        }
    }

    private function deny(): void
    {
        header('WWW-Authenticate: Basic realm="Restricted"');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}
