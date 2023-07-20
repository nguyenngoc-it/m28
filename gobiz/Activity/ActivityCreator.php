<?php

namespace Gobiz\Activity;

use Gobiz\Support\OptionsAccess;

class ActivityCreator extends OptionsAccess implements ActivityCreatorInterface
{
    const ID = 'id';
    const USERNAME = 'username';
    const NAME = 'name';
    const TENANT_ID = 'tenant_id';
    const IS_ADMIN = 'is_admin';

    /**
     * Make the options config
     *
     * @return array
     */
    protected function makeConfig()
    {
        return [
            static::ID => [
                static::PARAM_NORMALIZER => 'int',
            ],
            static::USERNAME => [
                static::PARAM_NORMALIZER => 'string',
            ],
            static::NAME => [
                static::PARAM_NORMALIZER => 'string',
            ],
            static::TENANT_ID => [
                static::PARAM_NORMALIZER => 'int',
            ],
            static::IS_ADMIN => [
                static::PARAM_NORMALIZER => 'bool',
                static::PARAM_DEFAULT => true,
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->get(static::ID);
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->get(static::USERNAME);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->get(static::NAME);
    }

    /**
     * @inheritDoc
     */
    public function getTenantId()
    {
        return $this->get(static::TENANT_ID);
    }

    /**
     * @inheritDoc
     */
    public function getIsAdmin()
    {
        return $this->get(static::IS_ADMIN);
    }
}