<?php

namespace Gobiz\Workflow;

interface SubjectInterface
{
    /**
     * Set subject's place
     *
     * @return string
     */
    public function getSubjectPlace();

    /**
     * Update current subject's place
     *
     * @param string $place
     * @throws WorkflowException
     */
    public function setSubjectPlace($place);
}