<?php

namespace App\Base;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Modules\App\Services\ResponseFactoryInterface;
use Modules\Service;
use Modules\User\Models\User;

class ExternalController extends BaseController
{
    /** @var Authenticatable|null|User $user */
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    /**
     * @return Request
     */
    protected function request()
    {
        return app(Request::class);
    }

    /**
     * @return ResponseFactoryInterface
     */
    protected function response()
    {
        return Service::app()->externalResponse();
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function transform($data)
    {
        return Service::app()->externalTransformer()->transform($data);
    }

    /**
     * @return User|Authenticatable|null
     */
    protected function user()
    {
        return Auth::user();
    }
}
