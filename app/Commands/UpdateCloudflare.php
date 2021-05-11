<?php

namespace App\Commands;

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Endpoints\DNS;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class UpdateCloudflare extends Command
{
    use Configurable;

    protected $signature = 'update-cloudflare';

    protected $description = 'Update Cloudflare';

    public function handle(): int
    {
        $this
            ->ensureConfigured()
            ->syncEntriesWithIp();
    }

    private function syncEntriesWithIp(): self
    {
        if (!$this->config['update']) {
            return $this;
        }

        $dns = new DNS($this->app->make(Guzzle::class));

        foreach ($this->config['domains'] as $domain => $data) {
            foreach ($data['entry'] as $entry) {
                $dns->updateRecordDetails($data['id'], $entry, ['content' => $this->config['ip']]);
            }
        }

        return $this;
    }

    public function schedule(Schedule $schedule): void
    {
        $schedule->command(static::class)->everyFiveMinutes();
    }
}
