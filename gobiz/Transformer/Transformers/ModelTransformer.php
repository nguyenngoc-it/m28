<?php

namespace Gobiz\Transformer\Transformers;

use Gobiz\Transformer\TransformerInterface;
use Illuminate\Database\Eloquent\Model;

class ModelTransformer implements TransformerInterface
{
    /**
     * Tự động format các field dạng datetime
     *
     * @var bool
     */
    public $formatDateTime = true;

    /**
     * Transform the data
     *
     * @param Model $data
     * @return mixed
     */
    public function transform($data)
    {
        $res = $data->attributesToArray();

        if (!$this->formatDateTime) {
            $res = array_merge($res, $data->only($data->getDates()));
        }

        return $res;
    }
}