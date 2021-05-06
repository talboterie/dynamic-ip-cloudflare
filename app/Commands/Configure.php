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

    protected $signature = 'configure';

    protected $description = 'Configure tool';

    protected string $apiKey;

    protected string $email;

    protected array $domains = [];

    protected array $data = [];

    public function handle(Filesystem $filesystem): int
    {
        return $this
            ->ensureNotConfigured($filesystem)
            ->gatherEmail()
            ->gatherApiKey()
            ->validateToken()
            ->gatherDomains()
            ->validateDomains()
            ->fetchIP()
            ->saveConfig($filesystem) ? 0 : 1;
    }

    protected function gatherEmail(): self
    {
        $this->email = $this->ask('Enter your email address');

        if (empty($this->email)) {
            return $this->gatherEmail();
        }

        $this->config['auth']['email'] = $this->email;

        return $this;
    }

    protected function gatherApiKey(): self
    {
        $this->apiKey = $this->secret('Enter your Cloudflare API Key');

        if (empty($this->apiKey)) {
            return $this->gatherApiKey();
        }

        $this->config['auth']['api_key'] = $this->apiKey;

        return $this;
    }

    protected function validateToken(): self
    {
        $key = new APIKey($this->email, $this->apiKey);
        $adapter = new Guzzle($key);
        $user = new User($adapter);

        if ($user->getUserID()) {
            return $this;
        }

        throw new RuntimeException('Email or API Key not valid');
    }

    protected function gatherDomains(): self
    {
        $domain = $this->ask('Provide a domain to update');

        if (empty($domain)) {
            return $this;
        }

        $this->domains[] = $domain;

        return $this->gatherDomains();
    }

    protected function validateDomains(): self
    {
        $key = new APIKey($this->email, $this->apiKey);
        $adapter = new Guzzle($key);

        $data = [];

        foreach ($this->domains as $domain) {
            $zone = (new Zones($adapter))->listZones($domain);

            foreach ($zone->result as $zone) {
                $data[$domain] = [
                    'id'    => $zone->id,
                    'entry' => [],
                ];
            }
        }

        $this->config['domains'] = $data;

        return $this;
    }

    protected function fetchIP(): self
    {
        $this->call('what-is-my-ip');

        return $this;
    }
}
