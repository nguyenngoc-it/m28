<?php

namespace Gobiz\Workflow;

use Illuminate\Support\Collection;

class Definition implements DefinitionInterface
{
    /**
     * @var array
     */
    protected $places;

    /**
     * @var Collection
     */
    protected $transitions;

    /**
     * Definition constructor
     *
     * @param array $places
     * @param Transition[] $transitions
     */
    public function __construct(array $places, array $transitions)
    {
        $this->places = $places;
        $this->transitions = Collection::make($transitions);
    }

    /**
     * Find transition by name
     *
     * @param string $name
     * @return Transition|null
     */
    public function findTransition($name)
    {
        return $this->transitions->firstWhere('name', $name);
    }

    /**
     * Find transition by place
     *
     * @param string $from
     * @param string $to
     * @return Transition|null
     */
    public function findTransitionByPlace($from, $to)
    {
        return $this->transitions->first(function (Transition $transition) use ($from, $to) {
            return $transition->from === $from && $transition->to === $to;
        });
    }

    /**
     * Get next transitions
     *
     * @param string $place
     * @return Transition[]
     */
    public function getNextTransitions($place)
    {
        return $this->transitions->where('from', $place)->values()->all();
    }

    /**
     * Get previous transitions
     *
     * @param string $place
     * @return Transition[]
     */
    public function getPreviousTransitions($place)
    {
        return $this->transitions->where('to', $place)->values()->all();
    }

    /**
     * Return true if place exists
     *
     * @param string $place
     * @return bool
     */
    public function hasPlace($place)
    {
        return in_array($place, $this->places);
    }

    /**
     * Get list places
     *
     * @return array
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * Get list transitions
     *
     * @return Transition[]
     */
    public function getTransitions()
    {
        return $this->transitions->all();
    }
}