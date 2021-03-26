<?php

namespace App\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use LaravelZero\Framework\Commands\Command;

class WhatIsMyIp extends Command
{
    protected $signature = 'what-is-my-ip';

    protected $description = 'What is my IP';

    protected $ip = null;

    public function handle(Filesystem $filesystem): int
    {
        return $this
            ->fetchCurrentIP()
            ->saveIP($filesystem) ? 0 : 1;
    }

    protected function fetchCurrentIP(): self
    {
        $client = new Client([
            'base_uri' => 'https://wtfismyip.com/'
        ]);

        $this->ip = $client->get('/text')->getBody()->getContents();

        return $this;
    }

    protected function saveIP(Filesystem $filesystem): bool
    {
        $filesystem->put('ip.txt', $this->ip);

        return true;
    }

    public function schedule(Schedule $schedule): void
    {
        $schedule->command(static::class)->everyFiveMinutes();
    }
}
