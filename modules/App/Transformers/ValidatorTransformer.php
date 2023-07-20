<?php

namespace Modules\App\Transformers;

use App\Base\Validator;
use Gobiz\Transformer\TransformerInterface;
use Gobiz\Transformer\Transformers\ValidationErrorTransformer;

class ValidatorTransformer implements TransformerInterface
{
    protected $transformer;

    /**
     * ValidatorTransformer constructor
     */
    public function __construct()
    {
        $this->transformer = new ValidationErrorTransformer();
    }

    /**
     * Transform the data
     *
     * @param Validator $validator
     * @return mixed
     */
    public function transform($validator)
    {
        return $this->transformer->transform($validator->getBaseValidator());
    }
}