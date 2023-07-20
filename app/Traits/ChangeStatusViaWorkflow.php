<?php

namespace App\Traits;

use App\Base\Model;
use Gobiz\Workflow\SubjectInterface;
use Gobiz\Workflow\WorkflowException;
use Gobiz\Workflow\WorkflowInterface;
use Modules\User\Models\User;

/**
 * Trait ChangeStatusViaWorkflow
 *
 * @mixin Model|SubjectInterface
 */
trait ChangeStatusViaWorkflow
{
    /**
     * @return WorkflowInterface
     */
    abstract public function getWorkflow();

    /**
     * Set subject's place
     *
     * @return string
     */
    public function getSubjectPlace()
    {
        return $this->getAttribute('status');
    }

    /**
     * Update current subject's place
     *
     * @param string $place
     * @throws WorkflowException
     */
    public function setSubjectPlace($place)
    {
        if (!$this->update(['status' => $place])) {
            throw new WorkflowException("Update status {$place} for order {$this->getKey()} failed");
        }
    }

    /**
     * Thay đổi trạng thái đơn
     *
     * @param string $status
     * @param User $creator
     * @param array $payload
     * @throws WorkflowException
     */
    public function changeStatus($status, User $creator, array $payload = [])
    {
        $this->getWorkflow()->change($this, $status, array_merge($payload, ['creator' => $creator]));
    }

    /**
     * Kiểm tra có thể đổi trạng thái đơn hay không
     *
     * @param string $status
     * @return bool
     */
    public function canChangeStatus($status)
    {
        return $this->getWorkflow()->canChange($this, $status);
    }
}
