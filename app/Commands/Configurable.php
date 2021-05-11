<?php

namespace App\Commands;

use App\Configuration;
use App\Exceptions\AlreadyConfiguredException;
use App\Exceptions\NotConfiguredException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

trait Configurable
{
    protected ?Configuration $config = null;

    protected function shouldLoadConfiguration(): void
    {
        if ($this->config === null) {
            try {
                $yaml = Yaml::parseFile('config.yml');
            } catch (ParseException $exception) {
                $yaml = [];
            }

            $this->config = new Configuration($yaml);
        }
    }

    protected function ensureConfigured(): self
    {
        $this->shouldLoadConfiguration();

        throw_unless($this->config->isConfigured(), NotConfiguredException::class);

        return $this;
    }

    protected function ensureNotConfigured(): self
    {
        $this->shouldLoadConfiguration();

        throw_if($this->config->isConfigured(), AlreadyConfiguredException::class);

        return $this;
    }

    protected function saveConfig(Filesystem $filesystem): bool
    {
        return $filesystem->put('config.yml',
            Yaml::dump($this->config->toArray(), 10, flags: Yaml::DUMP_NULL_AS_TILDE | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE));
    }
}
