<?php

namespace Gobiz\Transformer;

interface TransformerFinderInterface
{
    /**
     * Find the corresponding transformer of given data
     *
     * @param object $data
     * @return TransformerInterface|null
     */
    public function find($data);
}