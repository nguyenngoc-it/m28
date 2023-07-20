<?php

namespace Gobiz\Transformer;

use App\Base\Model;

interface TransformerInterface
{
    /**
     * Transform the data
     *
     * @param Model|object $data
     * @return mixed
     */
    public function transform($data);
}
