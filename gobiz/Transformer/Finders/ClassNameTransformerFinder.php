<?php

namespace Gobiz\Transformer\Finders;

use Gobiz\Transformer\TransformerFinderInterface;
use Illuminate\Contracts\Container\Container;
use Gobiz\Transformer\TransformerInterface;

class ClassNameTransformerFinder implements TransformerFinderInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var bool
     */
    protected $registered = false;

    /**
     * ClassNameTransformer constructor
     *
     * @param Container $container
     * @param string $className
     */
    public function __construct(Container $container, $className)
    {
        $this->container = $container;
        $this->className = $className;
    }

    /**
     * Find the corresponding transformer of given data
     *
     * @param object $data
     * @return TransformerInterface|null
     */
    public function find($data)
    {
        return $this->getTransformerFinder()->find($data);
    }

    /**
     * @return TransformerFinderInterface
     */
    protected function getTransformerFinder()
    {
        if (!$this->registered) {
            $this->registered = true;
            $this->container->singleton($this->className, $this->className);
        }

        return $this->container->make($this->className);
    }
}