<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private string $baseUrl = 'http://localhost:8000';

    private function post(string $path, array $data): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true) ?? [];
    }

    public function test_login_with_invalid_credentials(): void
    {
        $res = $this->post('/api/auth/login', [
            'email'    => 'tidakada@test.com',
            'password' => 'salah',
        ]);
        $this->assertFalse($res['success'] ?? true);
    }
}
