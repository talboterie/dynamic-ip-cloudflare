<?php

namespace App\Providers;

use App\Configuration;
use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIKey;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Yaml\Yaml;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bindIf(Guzzle::class, function () {
            if (file_exists('config.yml')) {
                $config = Yaml::parseFile('config.yml');
                $key = new APIKey($config['auth']['email'], $config['auth']['api_key']);

                return new Guzzle($key);
            }

            return null;
        });
    }
}
