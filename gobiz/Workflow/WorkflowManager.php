<?php

namespace Gobiz\Workflow;

use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;

class WorkflowManager implements WorkflowManagerInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var WorkflowInterface[]
     */
    protected $workflows = [];

    /**
     * WorkflowManager constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get workflow
     *
     * @param string $name
     * @return WorkflowInterface|null
     */
    public function get($name)
    {
        return $this->workflows[$name] ?? null;
    }

    /**
     * Add new workflow
     *
     * @param string $name
     * @param array|WorkflowInterface $workflow
     * @return WorkflowInterface
     */
    public function add($name, $workflow)
    {
        if ($workflow instanceof WorkflowInterface) {
            return $this->workflows[$name] = $workflow;
        }

        return $this->workflows[$name] = new Workflow(
            new Pipeline($this->container),
            new Definition($workflow['places'], $this->makeTransitions($workflow)),
            Arr::get($workflow, 'middleware', [])
        );
    }

    /**
     * @param array $workflow
     * @return array
     */
    protected function makeTransitions(array $workflow)
    {
        $transitions = [];

        foreach ($workflow['transitions'] as $index => $transition) {
            /*
            $transitions = [
                ['name' => 'xxx', 'from' => 'xxx', 'to' => 'xxx'],
                ...
            ];
            */
            if (is_int($index)) {
                $transitions[] = new Transition($transition['name'], $transition['from'], $transition['to']);
                continue;
            }

            /*
            $transitions = [
                'place1' => 'place2',
                'place1' => ['place2', 'place3'],
                'place1' => [
                    'transitionA' => 'place2',
                    'transitionB' => 'place3',
                    ...
                ],
                'place1' => [
                    ['name' => 'transitionA', 'to' => 'place2'],
                    ['name' => 'transitionB', 'to' => 'place3'],
                    ...
                ],
            ];
            */
            if (is_string($index)) {
                $transitions = array_merge($transitions, $this->makeTransitionsForFromPlace($index, $transition));
                continue;
            }
        }

        if (!empty($workflow['reverse_transitions'])) {
            $transitions = array_merge($transitions, $this->reverseTransitions($transitions));
        }

        return $transitions;
    }

    /**
     * @param string $from
     * @param string|array $config
     * @return array
     */
    protected function makeTransitionsForFromPlace($from, $config)
    {
        $transitions = [];
        foreach ((array)$config as $index => $transition) {
            $transition = is_string($transition) ? ['to' => $transition] : $transition;

            if (is_string($index)) {
                $transition['name'] = $index;
            }

            $name = Arr::get($transition, 'name', "{$from}_{$transition['to']}");

            $transitions[] = new Transition($name, $from, $transition['to']);
        }

        return $transitions;
    }

    /**
     * @param array $transitions
     * @return array
     */
    protected function reverseTransitions(array $transitions)
    {
        return array_map(function (Transition $transition) {
            $from = $transition->to;
            $to = $transition->from;

            return new Transition("{$from}_{$to}", $from, $to);
        }, $transitions);
    }
}