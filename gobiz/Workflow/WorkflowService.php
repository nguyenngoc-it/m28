<?php

namespace Gobiz\Workflow;

class WorkflowService
{
    /**
     * @return WorkflowManagerInterface
     */
    public static function workflows()
    {
        return app(WorkflowManagerInterface::class);
    }

    /**
     * Get workflow
     *
     * @param string $name
     * @return WorkflowInterface|null
     */
    public static function workflow($name)
    {
        return static::workflows()->get($name);
    }
}