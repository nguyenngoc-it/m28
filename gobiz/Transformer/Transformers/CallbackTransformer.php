<?php

namespace Gobiz\Transformer\Transformers;

use Closure;
use Gobiz\Transformer\TransformerInterface;

class CallbackTransformer implements TransformerInterface
{
    /**
     * @var Closure
     */
    protected $callback;

    /**
     * CallbackTransformer constructor
     *
     * @param Closure $callback
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Transform the data
     *
     * @param object $data
     * @return mixed
     */
    public function transform($data)
    {
        return call_user_func($this->callback, $data);
    }
}