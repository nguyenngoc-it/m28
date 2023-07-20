<?php

namespace Modules\Gobiz\Services;

use Gobiz\Support\RestApiResponse;
use Modules\Tenant\Models\Tenant;

interface M10ApiInterface
{
    /**
     * @param array $input
     * @return \Gobiz\Support\RestApiResponse|mixed|void
     */
    public function register(array $input);
}
