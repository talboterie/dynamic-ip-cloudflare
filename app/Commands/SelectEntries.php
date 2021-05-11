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
            ->ensureConfigured()
            ->fetchEntries()
            ->selectEntries()
            ->ensureEntriesExist()
            ->saveConfig($filesystem) ? 0 : 1;
    }

    private function fetchEntries(): self
    {
        $guzzle = $this->app->make(Guzzle::class);

        foreach ($this->config->domains() as $domain => $data) {
            $this->entries[$domain] = (new DNS($guzzle))->listRecords($data['id'])->result;
        }

        return $this;
    }

    private function selectEntries(): self
    {
        foreach ($this->entries as $domain => $entries) {
            $records = collect($entries)
                ->filter(function ($entry) {
                    return in_array($entry->type, ['A', 'CNAME', 'AAAA']);
                })
                ->mapWithKeys(function ($entry) {
                    return [$entry->id => "{$entry->type}: {$entry->name} ({$entry->content})"];
                });

            $choices = $records->values()->all();
            $ids = $records->flip();

            $choices[] = 'Create a new A entry';

            $answers = $this->choice("Select entries for [{$domain}] to keep updated", $choices, multiple: true);

            $ids = $ids->filter(fn($item, $index) => in_array($index, $answers))
                ->values()
                ->all();

            if (in_array('Create a new A entry', $answers)) {
                $ids[] = '0';
            }

            $this->config['domains'][$domain]['entry'] = $ids;
        }

        return $this;
    }

    private function ensureEntriesExist(): self
    {
        $dns = new DNS($this->app->make(Guzzle::class));

        foreach ($this->config['domains'] as $domain => $data) {
            foreach ($data['entry'] as &$entry) {
                if ($entry === '0') {
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
