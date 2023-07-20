<?php

namespace Gobiz\Transformer;

interface TransformerManagerInterface
{
    /**
     * Map object class name to transformer
     *
     * @param string $class
     * @param TransformerInterface $transformer
     * @return static
     */
    public function map($class, TransformerInterface $transformer);

    /**
     * Register the transformer finder
     *
     * @param TransformerFinderInterface $finder
     * @return static
     */
    public function finder(TransformerFinderInterface $finder);

    /**
     * Find the corresponding transformer of given object
     *
     * @param object $object
     * @return TransformerInterface|null
     */
    public function find($object);

    /**
     * Transform the given data
     *
     * @param mixed $data
     * @return mixed
     */
    public function transform($data);
}