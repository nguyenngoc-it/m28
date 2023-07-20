<?php

namespace Gobiz\Transformer\Transformers;

use DateTimeInterface;
use Gobiz\Support\DateTime;
use Gobiz\Transformer\TransformerInterface;

class DateTimeIso8601ZuluTransformer implements TransformerInterface
{
    /**
     * Transform the data
     *
     * @param DateTimeInterface $data
     * @return mixed
     */
    public function transform($data)
    {
        return (new DateTime($data))->toIso8601ZuluString();
    }
}