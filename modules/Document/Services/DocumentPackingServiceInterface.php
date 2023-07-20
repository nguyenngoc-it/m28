<?php

namespace Modules\Document\Services;

use Modules\User\Models\User;

interface DocumentPackingServiceInterface
{
    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user);
}
