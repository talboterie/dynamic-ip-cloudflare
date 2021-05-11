<?php

namespace App;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class Configuration implements Arrayable
{
    public function __construct(protected array $config) {}

    public function isConfigured(): bool
    {
        if (!key_exists('email', $this->auth()) || key_exists('key', $this->auth())) {
            return false;
        }

        return true;
    }

    public function shouldUpdate(): bool
    {
        return $this->config['should_update'] ?? false;
    }

    public function ip(?string $ip = null): ?string
    {
        if ($ip !== null && $ip !== Arr::get($this->config, 'ip')) {
            $this->config['should_update'] = true;
        }

        if ($ip !== null) {
            $this->config['ip'] = $ip;
        }

        return $this->config['ip'] ?? null;
    }

    public function auth(?string $email = null, ?string $key = null): array
    {
        if ($email !== null) {
            Arr::set($this->config, 'auth.email', $email);
        }

        if ($key !== null) {
            Arr::set($this->config, 'auth.key', $key);
        }

        return $this->config['auth'] ?? [];
    }

    public function domain(string $domain, ?string $id = null, ?array $entries = null): array
    {
        if (!key_exists($domain, $this->config['domains'] ?? [])) {
            $this->config['domains'][$domain] = ['id' => null, 'entries' => []];
        }

        if ($id !== null) {
            $this->config['domains'][$domain]['id'] = $id;
        }

        if ($entries !== null) {
            $this->config['domains'][$domain]['entries'] = $entries;
        }

        return $this->config['domains'][$domain];
    }

    public function domains(): array
    {
        return $this->config['domains'] ?? [];
    }

    public function toArray(): array
    {
        return $this->config;
    }
}
