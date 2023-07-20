<?php

namespace Gobiz\Activity;

use Carbon\Carbon;
use DateTimeInterface;
use Gobiz\Support\OptionsAccess;
use InvalidArgumentException;

class Activity extends OptionsAccess implements ActivityInterface
{
    const ID        = 'id';
    const CREATOR   = 'creator';
    const ACTION    = 'action';
    const OBJECTS   = 'objects';
    const PAYLOAD   = 'payload';
    const MESSAGE   = 'message';
    const IS_PUBLIC = 'public';
    const TIME      = 'time';

    /**
     * Make the options config
     *
     * @return array
     */
    protected function makeConfig()
    {
        return [
            static::ID => [
                static::PARAM_NORMALIZER => 'string',
            ],
            static::CREATOR => [
                static::PARAM_NORMALIZER => function ($input) {
                    return $this->normalizeCreator($input);
                },
                static::PARAM_DEFAULT => [],
            ],
            static::ACTION => [
                static::PARAM_REQUIRED => true,
                static::PARAM_NORMALIZER => 'string',
            ],
            static::OBJECTS => [
                static::PARAM_ALLOWED_TYPES => 'array',
                static::PARAM_DEFAULT => [],
            ],
            static::PAYLOAD => [
                static::PARAM_ALLOWED_TYPES => 'array',
                static::PARAM_DEFAULT => [],
            ],
            static::MESSAGE => [
                static::PARAM_NORMALIZER => 'string',
            ],
            static::IS_PUBLIC => [
                static::PARAM_NORMALIZER => 'bool',
            ],
            static::TIME => [
                static::PARAM_INSTANCEOF => DateTimeInterface::class,
                static::PARAM_DEFAULT => Carbon::now(),
            ],
        ];
    }

    /**
     * @param ActivityCreatorInterface|array $input
     * @return ActivityCreatorInterface
     * @throws InvalidArgumentException
     */
    protected function normalizeCreator($input)
    {
        if ($input instanceof ActivityCreatorInterface) {
            return $input;
        }

        if (is_array($input)) {
            return new ActivityCreator($input);
        }

        throw new InvalidArgumentException('The creator must is instance of ActivityCreatorInterface or is an array');
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
    public function getCreator()
    {
        return $this->get(static::CREATOR);
    }

    /**
     * @inheritDoc
     */
    public function getAction()
    {
        return $this->get(static::ACTION);
    }

    /**
     * @inheritDoc
     */
    public function getObjects()
    {
        return $this->get(static::OBJECTS);
    }

    /**
     * @inheritDoc
     */
    public function getPayload()
    {
        return $this->get(static::PAYLOAD);
    }

    /**
     * @inheritDoc
     */
    public function getPublic()
    {
        return $this->get(static::IS_PUBLIC);
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return $this->get(static::MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function getTime()
    {
        return $this->get(static::TIME);
    }

    /**
     * @inheritDoc
     */
    public function getActivityAsArray()
    {
        $creator = $this->getCreator();

        return [
            static::CREATOR => [
                ActivityCreator::ID => $creator->getId(),
                ActivityCreator::USERNAME => $creator->getUsername(),
                ActivityCreator::NAME => $creator->getName(),
                ActivityCreator::TENANT_ID => $creator->getTenantId(),
                ActivityCreator::IS_ADMIN => $creator->getIsAdmin()
            ],
            static::ACTION => $this->getAction(),
            static::OBJECTS => $this->getObjects(),
            static::PAYLOAD => $this->getPayload(),
            static::IS_PUBLIC => $this->getPublic(),
            static::MESSAGE => $this->getMessage(),
            static::TIME => $this->getTime(),
        ];
    }
}