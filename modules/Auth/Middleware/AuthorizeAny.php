<?php

namespace Modules\Auth\Middleware;

use Illuminate\Support\Facades\Gate;

class AuthorizeAny extends Authorize
{
    /**
     * @inheritDoc
     */
    protected function performAuthorize(array $abilities, array $arguments)
    {
        return Gate::any($abilities, $arguments);
    }
}
