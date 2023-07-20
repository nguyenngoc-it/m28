<?php

namespace Modules\User\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Commands\AddCountry;
use Modules\User\Commands\AddMerchant;
use Modules\Service;
use Modules\User\Commands\AddSupplier;
use Modules\User\Commands\AddWarehouse;
use Modules\User\Models\User;
use Modules\User\Transformers\UserDetailTransformer;
use Modules\User\Transformers\UserListTransformer;
use Modules\User\Validators\AddingUserCountryValidator;
use Modules\User\Validators\AddUserMerchantValidator;
use Modules\User\Validators\AddUserSupplierValidator;
use Modules\User\Validators\AddUserWarehouseValidator;
use Modules\User\Validators\ListUserValidator;

class UserController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filers             = $this->getQueryFilter();
        $filers['paginate'] = false;
        $users              = Service::user()->lists($filers);

        return $this->response()->success(['users' => $users]);
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function detail(User $user)
    {
        $user = (new UserDetailTransformer())->transform($user);
        return $this->response()->success($user);
    }

    /**
     * @return JsonResponse
     */
    public function listUserMerchants()
    {
        $filers = $this->getQueryFilter();

        $results = Service::user()->lists($filers);

        return $this->response()->success([
            'users' => array_map(function (User $user) {
                return (new UserListTransformer())->transform($user);
            }, $results->items()),
            'pagination' => $results
        ]);
    }

    /**
     * Tạo filter để query product
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(ListUserValidator::$keyRequests);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['tenant_id'] = $this->getAuthUser()->tenant_id;

        if (
            $this->request()->get('created_at_from') &&
            $this->request()->get('created_at_to')
        ) {
            $filter['created_at'] = [
                'from' => $this->request()->get('created_at_from'),
                'to' => $this->request()->get('created_at_to'),
            ];
        }

        return $filter;
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function addMerchant(User $user)
    {
        $creator   = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new AddUserMerchantValidator($user->tenant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $merchants = $validator->getMerchants();
        $user      = (new AddMerchant($user, $creator, $merchants))->handle();
        $user      = (new UserDetailTransformer())->transform($user);
        return $this->response()->success($user);
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function addSupplier(User $user)
    {
        $creator   = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new AddUserSupplierValidator($user->tenant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $suppliers = $validator->getSuppliers();
        $user      = (new AddSupplier($user, $creator, $suppliers))->handle();
        $user      = (new UserDetailTransformer())->transform($user);
        return $this->response()->success($user);
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function addWarehouse(User $user)
    {
        $creator   = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new AddUserWarehouseValidator($user->tenant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $warehouses = $validator->getWarehouses();
        $user       = (new AddWarehouse($user, $creator, $warehouses))->handle();
        $user       = (new UserDetailTransformer())->transform($user);
        return $this->response()->success($user);
    }

    /**
     * Gán thị trường cho 1 user
     *
     * @param User $user
     * @return JsonResponse
     */
    public function addCountry(User $user)
    {
        $creator   = $this->getAuthUser();
        $inputs    = $this->request()->only(['country_ids']);
        $validator = (new AddingUserCountryValidator($inputs));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user = (new AddCountry($user, $creator, $inputs))->handle();
        $user = (new UserDetailTransformer())->transform($user);
        return $this->response()->success($user);
    }
}
