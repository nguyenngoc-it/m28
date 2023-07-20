<?php

namespace Gobiz\Workflow;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;

class Workflow implements WorkflowInterface
{
    /**
     * @var Pipeline
     */
    protected $pipeline;

    /**
     * @var DefinitionInterface
     */
    protected $definition;

    /**
     * @var array
     */
    protected $middleware = [];

    /**
     * Workflow constructor
     *
     * @param Pipeline $pipeline
     * @param DefinitionInterface $definition
     * @param array $middleware
     */
    public function __construct(Pipeline $pipeline, DefinitionInterface $definition, array $middleware = [])
    {
        $this->pipeline = $pipeline;
        $this->definition = $definition;
        $this->middleware = $middleware;
    }

    /**
     * Get next places from given place
     *
     * @param string $place
     * @return array
     */
    public function getNextPlaces($place)
    {
        $places = Arr::pluck($this->definition->getNextTransitions($place), 'to');

        return $this->sortPlaces($places);
    }

    /**
     * Get previous places from given place
     *
     * @param string $place
     * @return array
     */
    public function getPreviousPlaces($place)
    {
        $places = Arr::pluck($this->definition->getPreviousTransitions($place), 'from');

        return $this->sortPlaces($places);
    }

    /**
     * @param array $places
     * @return array
     */
    protected function sortPlaces(array $places)
    {
        return Arr::sort($places, function ($place) {
            return array_search($place, $this->definition->getPlaces());
        });
    }

    /**
     * Get next transitions from given place
     *
     * @param string $place
     * @return Transition[]
     */
    public function getNextTransitions($place)
    {
        return $this->definition->getNextTransitions($place);
    }

    /**
     * Get previous transitions from given place
     *
     * @param string $place
     * @return Transition[]
     */
    public function getPreviousTransitions($place)
    {
        return $this->definition->getPreviousTransitions($place);
    }

    /**
     * Change subject's place
     *
     * @param SubjectInterface $subject
     * @param string $place
     * @param array $payload
     * @throws WorkflowException
     */
    public function change(SubjectInterface $subject, $place, array $payload = [])
    {
        $transition = $this->validateChangePlace($subject, $place);
        $this->applyTransition($subject, $transition, $payload);
    }

    /**
     * Return true if can change subject's place
     *
     * @param SubjectInterface $subject
     * @param string $place
     * @return bool
     */
    public function canChange(SubjectInterface $subject, $place)
    {
        try {
            $this->validateChangePlace($subject, $place);
            return true;
        } catch (WorkflowException $e) {
            return false;
        }
    }

    /**
     * @param SubjectInterface $subject
     * @param string $place
     * @return Transition
     * @throws WorkflowException
     */
    protected function validateChangePlace(SubjectInterface $subject, $place)
    {
        if (!$transition = $this->definition->findTransitionByPlace($subject->getSubjectPlace(), $place)) {
            throw new WorkflowException("Can't change place from {$subject->getSubjectPlace()} to {$place}");
        }

        return $transition;
    }

    /**
     * Apply transition for subject
     *
     * @param SubjectInterface $subject
     * @param $transitionName
     * @param array $payload
     * @throws WorkflowException
     */
    public function apply(SubjectInterface $subject, $transitionName, array $payload = [])
    {
        $transition = $this->validateApplyTransition($subject, $transitionName);
        $this->applyTransition($subject, $transition, $payload);
    }

    /**
     * Return true if can apply transition for subject
     *
     * @param SubjectInterface $subject
     * @param $transitionName
     * @return bool
     */
    public function canApply(SubjectInterface $subject, $transitionName)
    {
        try {
            $this->validateApplyTransition($subject, $transitionName);
            return true;
        } catch (WorkflowException $e) {
            return false;
        }
    }

    /**
     * @param SubjectInterface $subject
     * @param string $transitionName
     * @return Transition
     * @throws WorkflowException
     */
    protected function validateApplyTransition(SubjectInterface $subject, $transitionName)
    {
        if (!$transition = $this->definition->findTransition($transitionName)) {
            throw new WorkflowException("The transition {$transitionName} not found");
        }

        if ($subject->getSubjectPlace() !== $transition->from) {
            throw new WorkflowException("The current subject place invalid. Subject place: {$subject->getSubjectPlace()}, Transition from: {$transition->from}");
        }

        return $transition;
    }

    /**
     * @param SubjectInterface $subject
     * @param Transition $transition
     * @param array $payload
     */
    protected function applyTransition(SubjectInterface $subject, Transition $transition, array $payload = [])
    {
        $this->pipeline
            ->send(new ApplyTransitionCommand($this, $subject, $transition, $payload))
            ->through($this->middleware)
            ->then(function () use ($subject, $transition) {
                $subject->setSubjectPlace($transition->to);
            });
    }

    /**
     * Get definition handler
     *
     * @return DefinitionInterface
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Get list middleware
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }
}