<?php

namespace App\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use LaravelZero\Framework\Commands\Command;

class WhatIsMyIp extends Command
{
    use Configurable;

    protected $signature = 'what-is-my-ip';

    protected $description = 'What is my IP';

    protected $ip = null;

    public function handle(Filesystem $filesystem): int
    {
        return $this
            ->ensureConfigured()
            ->fetchCurrentIP()
            ->saveConfig($filesystem) ? 0 : 1;
    }

    private function fetchCurrentIP(): self
    {
        $client = new Client([
            'base_uri' => 'https://wtfismyip.com/',
        ]);

        $ip = trim($client->get('/text')->getBody()->getContents());

        $this->config['update'] = $ip !== ($this->config['ip'] ?? 0);

        $this->config['ip'] = $ip;

        return $this;
    }

    public function schedule(Schedule $schedule): void
    {
        $schedule->command(static::class)->everyMinute();
    }
}
