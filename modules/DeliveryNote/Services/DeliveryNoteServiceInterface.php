<?php

namespace Modules\DeliveryNote\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\User\Models\User;

interface DeliveryNoteServiceInterface
{
    /**
     * @param array $input
     * @param User $user
     */
    public function createDeliveryNote(array $input, User $user);

    /**
     * @param array $filter
     */
    public function listDeliveryNote(array $filter);

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);
}
