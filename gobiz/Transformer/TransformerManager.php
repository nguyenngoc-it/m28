<?php

namespace Gobiz\Transformer;

class TransformerManager implements TransformerManagerInterface
{
    /**
     * The transformers mapping
     *
     * @var TransformerInterface[]
     */
    protected $transformers = [];

    /**
     * The registered transformer finders
     *
     * @var TransformerFinderInterface[]
     */
    protected $finders = [];

    /**
     * The default transformer
     *
     * @var TransformerInterface|null
     */
    protected $defaultTransformer;

    /**
     * TransformerManager constructor
     *
     * @param null|TransformerInterface $defaultTransformer
     */
    public function __construct($defaultTransformer = null)
    {
        $this->defaultTransformer = $defaultTransformer;
    }

    /**
     * Map object class name to transformer
     *
     * @param string $class
     * @param TransformerInterface $transformer
     * @return static
     */
    public function map($class, TransformerInterface $transformer)
    {
        $this->transformers[$class] = $transformer;

        return $this;
    }

    /**
     * Register the transformer finder
     *
     * @param TransformerFinderInterface $finder
     * @return static
     */
    public function finder(TransformerFinderInterface $finder)
    {
        $this->finders[] = $finder;

        return $this;
    }

    /**
     * Find the corresponding transformer of given object
     *
     * @param object $object
     * @return TransformerInterface|null
     */
    public function find($object)
    {
        if (!is_object($object)) {
            return null;
        }

        return $this->findByObject($object) ?: $this->findByFinders($object);
    }

    /**
     * Find the transformer of given object
     *
     * @param object $object
     * @return TransformerInterface|null
     */
    protected function findByObject($object)
    {
        foreach ($this->transformers as $class => $transformer) {
            if ($object instanceof $class) {
                return $transformer;
            }
        }

        return null;
    }

    /**
     * Find the transformer by finders
     *
     * @param object $data
     * @return TransformerInterface|null
     */
    protected function findByFinders($data)
    {
        foreach ($this->finders as $finder) {
            if ($transformer = $finder->find($data)) {
                return $transformer;
            }
        }

        return null;
    }

    /**
     * Transform the given data
     *
     * @param mixed $data
     * @return mixed
     */
    public function transform($data)
    {
        if (is_array($data)) {
            return array_map(function ($item) {
                return $this->transform($item);
            }, $data);
        }

        if (!is_object($data)) {
            return $data;
        }

        $transformer = $this->find($data) ?: $this->defaultTransformer;

        if ($transformer) {
            return $this->transform($transformer->transform($data));
        }

        return $data;
    }
}