<?php

namespace Gobiz\Workflow;

interface DefinitionInterface
{
    /**
     * Find transition by name
     *
     * @param string $name
     * @return Transition|null
     */
    public function findTransition($name);

    /**
     * Find transition by place
     *
     * @param string $from
     * @param string $to
     * @return Transition|null
     */
    public function findTransitionByPlace($from, $to);

    /**
     * Get next transitions
     *
     * @param string $place
     * @return Transition[]
     */
    public function getNextTransitions($place);

    /**
     * Get previous transitions
     *
     * @param string $place
     * @return Transition[]
     */
    public function getPreviousTransitions($place);

    /**
     * Return true if place exists
     *
     * @param string $place
     * @return bool
     */
    public function hasPlace($place);

    /**
     * Get list places
     *
     * @return array
     */
    public function getPlaces();

    /**
     * Get list transitions
     *
     * @return Transition[]
     */
    public function getTransitions();
}