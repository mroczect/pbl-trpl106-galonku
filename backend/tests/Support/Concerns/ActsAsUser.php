<?php
namespace Tests\Support\Concerns;

trait ActsAsUser
{
    protected ?string $authToken = null;

    protected function loginAs(string $email, string $password): self
    {
        $res = $this->post('/api/v1/auth/login', compact('email', 'password'));
        if (!$res->json('success')) {
            throw new \RuntimeException("Login failed for $email: " . $res->body);
        }
        $this->authToken = $res->json('data.access_token');
        return $this;
    }

    protected function loginAsAdmin(): self     { return $this->loginAs('admin@galonku.com', 'admin123'); }
    protected function loginAsKurir(): self     { return $this->loginAs('agent@galonku.com', 'agent123'); }
    protected function loginAsPelanggan(): self { return $this->loginAs('customer@galonku.com', 'customer123'); }

    protected function withAuth(array $extra = []): array
    {
        if ($this->authToken === null) {
            throw new \RuntimeException('Not authenticated. Call loginAs*() first.');
        }
        return array_merge(['Authorization' => 'Bearer ' . $this->authToken], $extra);
    }
}
