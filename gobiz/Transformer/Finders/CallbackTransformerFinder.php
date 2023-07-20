<?php

namespace Gobiz\Transformer\Finders;

use Closure;
use Gobiz\Transformer\TransformerFinderInterface;
use Gobiz\Transformer\TransformerInterface;

class CallbackTransformerFinder implements TransformerFinderInterface
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
     * Find the corresponding transformer of given data
     *
     * @param object $data
     * @return TransformerInterface|null
     */
    public function find($data)
    {
        return call_user_func($this->callback, $data);
    }
}