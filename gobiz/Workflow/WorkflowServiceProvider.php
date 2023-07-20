<?php

namespace Gobiz\Workflow;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(WorkflowManagerInterface::class, function () {
            return $this->makeWorkflowManager();
        });
    }

    protected function makeWorkflowManager()
    {
        $manager = new WorkflowManager($this->app);

        foreach (config('workflow.workflows') as $name => $workflow) {
            $manager->add($name, $workflow);
        }

        return $manager;
    }

    public function provides()
    {
        return [WorkflowManagerInterface::class];
    }
}