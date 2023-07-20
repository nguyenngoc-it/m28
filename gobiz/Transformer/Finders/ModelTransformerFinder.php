<?php

namespace Gobiz\Transformer\Finders;

use Gobiz\Transformer\TransformerFinderInterface;
use Gobiz\Transformer\TransformerInterface;
use Gobiz\Transformer\Transformers\ModelTransformer;
use Illuminate\Database\Eloquent\Model;

class ModelTransformerFinder implements TransformerFinderInterface
{
    /**
     * @var TransformerInterface
     */
    protected $modelTransformer;

    /**
     * ModelTransformerFinder constructor
     */
    public function __construct()
    {
        $this->modelTransformer = new ModelTransformer();
    }

    /**
     * Find the corresponding transformer of given data
     *
     * @param object $data
     * @return TransformerInterface|null
     */
    public function find($data)
    {
        return $data instanceof Model ? $this->modelTransformer : null;
    }
}