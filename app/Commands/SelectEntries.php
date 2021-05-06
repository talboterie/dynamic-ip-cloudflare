<?php

namespace App\Commands;

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Endpoints\DNS;
use Illuminate\Filesystem\Filesystem;
use LaravelZero\Framework\Commands\Command;

class SelectEntries extends Command
{
    use Configurable;

    protected $signature = 'select-entries';

    protected $description = 'Select entries for each domains to keep updated';

    protected array $entries = [];

    public function handle(Filesystem $filesystem): int
    {
        return $this
            ->ensureConfigured($filesystem)
            ->fetchEntries()
            ->selectEntries()
            ->ensureEntriesExist()
            ->saveConfig($filesystem) ? 0 : 1;
    }

    protected function fetchEntries(): self
    {
        $guzzle = $this->app->make(Guzzle::class);

        foreach ($this->config['domains'] as $domain => $data) {
            $this->entries[$domain] = (new DNS($guzzle))->listRecords($data['id'])->result;
        }

        return $this;
    }

    protected function selectEntries(): self
    {
        foreach ($this->entries as $domain => $entries) {
            $records = collect($entries)
                ->filter(function ($entry) {
                    return in_array($entry->type, ['A', 'CNAME', 'AAAA']);
                })
                ->mapWithKeys(function ($entry) {
                    return [$entry->id => "{$entry->type}: {$entry->name} ({$entry->content})"];
                })
                ->all();

            $records[0] = 'Create a new A entry';

            $choices = $this->choice("Select entries for [{$domain}] to keep updated", $records, multiple: true);

            $this->config['domains'][$domain]['entry'] = $choices;
        }

        return $this;
    }

    protected function ensureEntriesExist(): self
    {
        $dns = new DNS($this->app->make(Guzzle::class));

        foreach ($this->config['domains'] as $domain => $data) {
            foreach ($data['entry'] as &$entry) {
                if ($entry === '0' || $entry === 'Create a new A entry') {
                    $name = $this->ask("Which domain name should be used for the new A entry for {$domain}?");
                    $dns->addRecord($data['id'], 'A', $name, $this->config['ip']);

                    $entry = $dns->getBody()->result->id;
                }
            }

            $this->config['domains'][$domain] = $data;
        }

        return $this;
    }
}
