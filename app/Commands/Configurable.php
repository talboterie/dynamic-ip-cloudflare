<?php

namespace App\Commands;

use App\Exceptions\AlreadyConfiguredException;
use App\Exceptions\NotConfiguredException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

trait Configurable
{
    protected array $config;

    protected function ensureConfigured(Filesystem $filesystem): self
    {
        throw_unless($filesystem->exists('config.yml'), NotConfiguredException::class);

        $this->config = Yaml::parseFile('config.yml');

        return $this;
    }

    protected function ensureNotConfigured(Filesystem $filesystem): self
    {
        throw_if($filesystem->exists('config.yml'), AlreadyConfiguredException::class);

        return $this;
    }

    protected function saveConfig(Filesystem $filesystem): bool
    {
        return $filesystem->put('config.yml',
            Yaml::dump($this->config, 10, flags: Yaml::DUMP_NULL_AS_TILDE | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE));
    }
}
