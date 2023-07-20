<?php

namespace Gobiz\Email;

use Gobiz\Log\LogService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class EmailServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(EmailProviderManagerInterface::class, function () {
            return $this->makeEmailProviderManager();
        });
    }

    /**
     * @return EmailProviderManager
     */
    protected function makeEmailProviderManager()
    {
        $manager = new EmailProviderManager($this->app);

        foreach (config('email.providers', []) as $provider => $config) {
            $manager->register($provider, $this->makeEmailProvider($provider, $config));
        }

        return $manager;
    }

    /**
     * @param $provider
     * @param array $config
     * @return EmailProviderInterface
     */
    protected function makeEmailProvider($provider, array $config)
    {
        switch ($provider) {
            case 'iris':
                return new IrisEmailProvider($config, LogService::logger('iris'));
            default:
                throw new InvalidArgumentException("The email provider [{$provider}] invalid");
        }
    }

    public function provides()
    {
        return [EmailProviderManagerInterface::class];
    }
}