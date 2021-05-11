<?php

namespace App\Commands;

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Endpoints\Zones;
use Illuminate\Filesystem\Filesystem;
use LaravelZero\Framework\Commands\Command;

class UpdateDomains extends Command
{
    use Configurable;

    protected $signature = 'update-domains {--add=* : Domains to add}';

    protected $description = 'Update domains list';

    protected array $domains = [];

    public function handle(Filesystem $filesystem): int
    {
        return $this
            ->ensureConfigured()
            ->gatherDomains()
            ->validateDomains()
            ->saveConfig($filesystem);
    }

    private function gatherDomains(): self
    {
        if (empty($domains = (array) $this->option('add') ?? [])) {
            while (!empty($domain = $this->ask('Type a domain to add'))) {
                $domains[] = $domain;
            }
        }

        $this->domains = $domains;

        return $this;
    }

    private function validateDomains(): self
    {
        $adapter = $this->app->make(Guzzle::class);

        foreach ($this->domains as $domain) {
            $zone = (new Zones($adapter))->listZones($domain);

            foreach ($zone->result as $zone) {
                $this->config->domain(domain: $domain, id: $zone->id);
            }
        }

        return $this;
    }
}
