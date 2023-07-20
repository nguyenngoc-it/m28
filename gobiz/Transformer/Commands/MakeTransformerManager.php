<?php

namespace Gobiz\Transformer\Commands;

use Gobiz\Transformer\Finders\ClassNameTransformerFinder;
use Gobiz\Transformer\Finders\ModelTransformerFinder;
use Gobiz\Transformer\TransformerFinderInterface;
use Gobiz\Transformer\TransformerInterface;
use Gobiz\Transformer\TransformerManager;
use Gobiz\Transformer\TransformerManagerInterface;
use Gobiz\Transformer\Transformers\ClassNameTransformer;
use Gobiz\Transformer\Transformers\DefaultTransformer;
use Gobiz\Transformer\Transformers\MessageBagTransformer;
use Gobiz\Transformer\Transformers\PaginatorTransformer;
use Gobiz\Transformer\Transformers\ValidationErrorTransformer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class MakeTransformerManager
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    public $transformers = [];

    /**
     * @var array
     */
    public $transformerFinders;

    /**
     * @var array
     */
    public $defaultTransformers = [
        Validator::class => ValidationErrorTransformer::class,
        LengthAwarePaginator::class => PaginatorTransformer::class,
        MessageBag::class => MessageBagTransformer::class,
    ];

    /**
     * @var array
     */
    public $defaultTransformerFinder = [
        ModelTransformerFinder::class,
    ];

    /**
     * MakeTransformerManager constructor
     *
     * @param array $transformers
     * @param array $transformerFinders
     * @param array $defaultTransformers
     */
    public function __construct(array $transformers, array $transformerFinders, array $defaultTransformers = null)
    {
        $this->transformers = $transformers;
        $this->transformerFinders = $transformerFinders;
        $this->defaultTransformers = $defaultTransformers ?: $this->defaultTransformers;
        $this->container = app();
    }

    /**
     * @return TransformerManagerInterface
     */
    public function handle()
    {
        $transformers = new TransformerManager(new DefaultTransformer());

        foreach ($this->getTransformers() as $class => $transformer) {
            $transformers->map($class, $this->normalizeTransformer($transformer));
        }

        foreach ($this->getTransformerFinders() as $finder) {
            $transformers->finder($this->normalizeTransformerFinder($finder));
        }

        return $transformers;
    }

    /**
     * @return array
     */
    protected function getTransformers()
    {
        $defaultTransformers = Arr::except($this->defaultTransformers, array_keys($this->transformers));

        return array_merge($this->transformers, $defaultTransformers);
    }

    /**
     * @param string|TransformerInterface $transformer
     * @return TransformerInterface
     * @throws InvalidArgumentException
     */
    protected function normalizeTransformer($transformer)
    {
        if ($transformer instanceof TransformerInterface) {
            return $transformer;
        }

        if (is_string($transformer)) {
            return new ClassNameTransformer($this->container, $transformer);
        }

        throw new InvalidArgumentException('The transformer invalid');
    }

    /**
     * @return array
     */
    protected function getTransformerFinders()
    {
        return array_merge($this->transformerFinders, $this->defaultTransformerFinder);
    }

    /**
     * @param string|TransformerFinderInterface $finder
     * @return TransformerFinderInterface
     * @throws InvalidArgumentException
     */
    protected function normalizeTransformerFinder($finder)
    {
        if ($finder instanceof TransformerFinderInterface) {
            return $finder;
        }

        if (is_string($finder)) {
            return new ClassNameTransformerFinder($this->container, $finder);
        }

        throw new InvalidArgumentException('The transformer finder invalid');
    }
}
