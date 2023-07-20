<?php

namespace Gobiz\Email;

use Gobiz\Support\Manager;

class EmailProviderManager extends Manager implements EmailProviderManagerInterface
{
    /**
     * Get the storage key of driver
     *
     * @param string $driver
     * @return string
     */
    protected function makeStorageKey($driver)
    {
        return EmailProviderInterface::class . '.' . $driver;
    }
}