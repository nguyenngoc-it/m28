<?php

namespace Gobiz\Workflow;

interface WorkflowManagerInterface
{
    /**
     * Get workflow
     *
     * @param string $name
     * @return WorkflowInterface|null
     */
    public function get($name);

    /**
     * Add new workflow
     *
     * @param string $name
     * @param array|WorkflowInterface $workflow
     * @return WorkflowInterface
     */
    public function add($name, $workflow);
}