<?php

namespace Modules\DeliveryNote\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\DeliveryNote\Commands\CreateDeliveryNote;
use Modules\DeliveryNote\Commands\ListDeliveryNote;
use Modules\User\Models\User;
use Modules\DeliveryNote\Models\DeliveryNote;

class DeliveryNoteService implements DeliveryNoteServiceInterface
{
    /**
     * @param array $input
     * @param User $user
     * @return DeliveryNote
     */
    public function createDeliveryNote(array $input, User $user)
    {
        return (new CreateDeliveryNote($input, $user))->handle();
    }

    /**
     * @param array $filter
     */
    public function listDeliveryNote(array $filter)
    {
        return (new ListDeliveryNote($filter))->handle();
    }

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new DeliveryNoteQuery())->query($filter);
    }

}
