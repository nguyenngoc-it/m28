<?php

namespace Gobiz\Workflow;

use Closure;

interface WorkflowMiddlewareInterface
{
    /**
     * Handle when change subject's place
     *
     * @param ApplyTransitionCommand $command
     * @param Closure $next
     * @return mixed
     */
    public function handle(ApplyTransitionCommand $command, Closure $next);
}