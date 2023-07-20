<?php

namespace Gobiz\Transformer\Finders;

use Gobiz\Transformer\Transformers\ClassNameTransformer;
use Illuminate\Contracts\Container\Container;
use Gobiz\Transformer\TransformerFinderInterface;
use Gobiz\Transformer\TransformerInterface;

abstract class FormattedTransformerFinder implements TransformerFinderInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * The class name format of the transformer
     *
     * @var string
     */
    protected $format;

    /**
     * The param name of transformer in format
     *
     * @var string
     */
    protected $param = '{name}';

    /**
     * FormattedTransformerFinder constructor
     *
     * @param Container $container
     * @param string $format
     * @param string $param
     */
    public function __construct(Container $container, $format, $param = null)
    {
        $this->container = $container;
        $this->format = $format;
        $this->param = $param ?: $this->param;
    }

    /**
     * Get the corresponding transformer name of given data
     *
     * @param object $data
     * @return string|null
     */
    abstract protected function getTransformerName($data);

    /**
     * Find the corresponding transformer of given data
     *
     * @param object $data
     * @return TransformerInterface|null
     */
    public function find($data)
    {
        return ($name = $this->getTransformerName($data))
            ? new ClassNameTransformer($this->container, $this->makeTransformerClass($name))
            : null;
    }

    /**
     * Make transformer class from name
     *
     * @param string $name
     * @return string
     */
    protected function makeTransformerClass($name)
    {
        return strtr($this->format, [$this->param => $name]);
    }

    /**
     * Get the class name format of the transformer
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * The param name of transformer in format
     *
     * @return string
     */
    public function getParam()
    {
        return $this->param;
    }
}