<?php

namespace Gobiz\Transformer;

use Gobiz\Transformer\Commands\MakeTransformerManager;
use Gobiz\Transformer\Transformers\ValidationErrorTransformer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Validator;
use Gobiz\Transformer\Transformers\MessageBagTransformer;
use Gobiz\Transformer\Transformers\PaginatorTransformer;

class TransformerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var bool
     */
    protected $defer = true;

    /**
     * @var array
     */
    protected $defaultTransformers = [
        Validator::class => ValidationErrorTransformer::class,
        LengthAwarePaginator::class => PaginatorTransformer::class,
        MessageBag::class => MessageBagTransformer::class,
    ];

    /**
     * Register service
     */
    public function register()
    {
        $this->app->singleton(TransformerManagerInterface::class, function () {
            return $this->makeTransformerManager();
        });
    }

    /**
     * @return TransformerManagerInterface
     */
    protected function makeTransformerManager()
    {
        return (new MakeTransformerManager(
            config('api.transformers', []),
            config('api.transformer_finders', []),
            $this->defaultTransformers
        ))->handle();
    }

    /**
     * @return array
     */
    public function provides()
    {
        return [
            TransformerManagerInterface::class,
        ];
    }
}