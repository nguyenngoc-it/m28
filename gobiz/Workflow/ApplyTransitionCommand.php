<?php

namespace Gobiz\Workflow;

use Illuminate\Support\Arr;

class ApplyTransitionCommand
{
    /**
     * @var WorkflowInterface
     */
    public $workflow;

    /**
     * @var SubjectInterface
     */
    public $subject;

    /**
     * @var Transition
     */
    public $transition;

    /**
     * @var array
     */
    public $payload = [];

    /**
     * ApplyTransition constructor
     *
     * @param Workflow $workflow
     * @param SubjectInterface $subject
     * @param Transition $transition
     * @param array $payload
     */
    public function __construct(Workflow $workflow, SubjectInterface $subject, Transition $transition, array $payload = [])
    {
        $this->workflow = $workflow;
        $this->subject = $subject;
        $this->transition = $transition;
        $this->payload = $payload;
    }

    /**
     * Get payload value
     *
     * @param null|string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPayload($key = null, $default = null)
    {
        return Arr::get($this->payload, $key, $default);
    }
}