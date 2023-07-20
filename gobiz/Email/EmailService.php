<?php

namespace Gobiz\Email;

class EmailService
{
    /**
     * @return EmailProviderManagerInterface
     */
    public static function providers()
    {
        return app(EmailProviderManagerInterface::class);
    }

    /**
     * @param string|null $provider
     * @return EmailProviderInterface
     */
    public static function email($provider = null)
    {
        return static::providers()->get($provider ?: config('email.default'));
    }
}