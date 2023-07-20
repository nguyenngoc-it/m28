<?php

namespace Gobiz\Transformer\Transformers;

use Illuminate\Contracts\Support\MessageBag;
use Gobiz\Transformer\TransformerInterface;

class MessageBagTransformer implements TransformerInterface
{
    /**
     * Transform the data
     *
     * @param MessageBag $messages
     * @return mixed
     */
    public function transform($messages)
    {
        return array_map('reset', $messages->toArray());
    }
}