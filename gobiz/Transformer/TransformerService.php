<?php

namespace Gobiz\Transformer;

class TransformerService
{
    /**
     * @return TransformerManagerInterface
     */
    public static function transformers()
    {
        return app(TransformerManagerInterface::class);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public static function transform($data)
    {
        return static::transformers()->transform($data);
    }
}