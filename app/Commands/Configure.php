<?php

namespace App\Commands;

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Endpoints\User;
use Cloudflare\API\Endpoints\Zones;
use Illuminate\Filesystem\Filesystem;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;

class Configure extends Command
{
    use Configurable;

    protected $signature = 'configure {--domains : Domains to update}';

    protected $description = 'Configure tool';

    protected string $apiKey;

    protected string $email;

    protected array $domains = [];

    protected array $data = [];

    public function handle(Filesystem $filesystem): int
    {
        return $this
            ->ensureNotConfigured()
            ->gatherEmail()
            ->gatherApiKey()
            ->validateToken()
            ->saveConfig($filesystem) ? 0 : 1;
    }

    private function gatherEmail(): self
    {
        $this->email = $this->ask('Enter your email address');

        if (empty($this->email)) {
            return $this->gatherEmail();
        }

        $this->config->auth(email: $this->email);

        return $this;
    }

    private function gatherApiKey(): self
    {
        $this->apiKey = $this->secret('Enter your Cloudflare API Key');

        if (empty($this->apiKey)) {
            return $this->gatherApiKey();
        }

        $this->config->auth(key: $this->apiKey);

        return $this;
    }

    private function validateToken(): self
    {
        $key = new APIKey($this->email, $this->apiKey);
        $adapter = new Guzzle($key);
        $user = new User($adapter);

        if ($user->getUserID()) {
            return $this;
        }

        throw new RuntimeException('Email or API Key not valid');
    }
}
