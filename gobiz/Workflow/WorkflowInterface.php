<?php

namespace Gobiz\Workflow;

interface WorkflowInterface
{
    /**
     * Get next places from given place
     *
     * @param string $place
     * @return array
     */
    public function getNextPlaces($place);

    /**
     * Get previous places from given place
     *
     * @param string $place
     * @return array
     */
    public function getPreviousPlaces($place);

    /**
     * Get next transitions from given place
     *
     * @param string $place
     * @return Transition[]
     */
    public function getNextTransitions($place);

    /**
     * Get previous transitions from given place
     *
     * @param string $place
     * @return Transition[]
     */
    public function getPreviousTransitions($place);

    /**
     * Change subject's place
     *
     * @param SubjectInterface $subject
     * @param string $place
     * @param array $payload
     * @throws WorkflowException
     */
    public function change(SubjectInterface $subject, $place, array $payload = []);

    /**
     * Return true if can change subject's place
     *
     * @param SubjectInterface $subject
     * @param string $place
     * @return bool
     */
    public function canChange(SubjectInterface $subject, $place);

    /**
     * Apply transition for subject
     *
     * @param SubjectInterface $subject
     * @param $transitionName
     * @param array $payload
     * @throws WorkflowException
     */
    public function apply(SubjectInterface $subject, $transitionName, array $payload = []);

    /**
     * Return true if can apply transition for subject
     *
     * @param SubjectInterface $subject
     * @param $transitionName
     * @return bool
     */
    public function canApply(SubjectInterface $subject, $transitionName);

    /**
     * Get definition handler
     *
     * @return DefinitionInterface
     */
    public function getDefinition();
}