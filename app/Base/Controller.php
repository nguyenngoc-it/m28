<?php

namespace App\Base;

use Gobiz\Transformer\TransformerService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Modules\App\Services\ResponseFactoryInterface;
use Modules\Service;
use Modules\User\Models\User;

abstract class Controller extends BaseController
{
    /** @var Authenticatable|User|null $user */
    protected $user;
    /** @var Request $requests */
    protected $requests;

    public function __construct()
    {
        $this->user     = $this->getAuthUser();
        $this->requests = $this->request();
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
        return Service::app()->response();
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function transform($data)
    {
        return TransformerService::transform($data);
    }

    /**
     * @return User|Authenticatable|null
     */
    protected function getAuthUser()
    {
        return Auth::user();
    }
}
