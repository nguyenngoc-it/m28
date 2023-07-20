<?php

namespace Modules\PurchasingManager\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\PurchasingManager\Models\PurchasingAccount;
use Modules\PurchasingManager\Services\PurchasingAccountEvent;
use Modules\PurchasingManager\Validators\BalancingPurchasingAccountValidator;
use Modules\PurchasingManager\Validators\CreatingPurchasingAccountValidator;
use Modules\PurchasingManager\Validators\DeletingPurchasingAccountValidator;
use Modules\PurchasingManager\Validators\UpdatingPurchasingAccountValidator;
use Modules\Service;

class PurchasingAccountController extends Controller
{
    /**
     * Tạo filter để query product
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs              = $inputs ?: [
            'username',
            'purchasing_service_id',
            'status',
            'merchant_id',
        ];
        $filter              = $this->request()->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter  = $this->getQueryFilter();
        $results = Service::purchasingManager()->listPurchasingAccounts($filter);

        return $this->response()->success([
            'purchasing_accounts' => $results->items(),
            'pagination' => $results,
        ]);
    }


    /**
     * @return JsonResponse
     */
    public function create()
    {
        $inputs    = $this->requests->only([
            'purchasing_service_id',
            'merchant_id',
            'username',
            'password',
            'pin_code',
        ]);
        $validator = new CreatingPurchasingAccountValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        /**
         * Nếu là tài khoản đã có trước đó thì khôi phục lại
         */
        $deletePurchasingAccount = $validator->getDeletePurchasingAccount();
        if ($deletePurchasingAccount) {
            $deletePurchasingAccount->restore();
            $deletePurchasingAccount->status = PurchasingAccount::STATUS_ACTIVE;
            $deletePurchasingAccount->save();
            $validator->getDeletePurchasingAccount()->logActivity(PurchasingAccountEvent::CREATE, $this->user);
            return $this->response()->success(['purchasing_account' => $validator->getDeletePurchasingAccount()]);
        }

        return $this->response()->success(
            [
                'purchasing_account' => Service::purchasingManager()->createPurchasingAccount($inputs, $validator->getAccessToken(), $this->user),
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function update($id)
    {
        $inputs       = $this->requests->only([
            'password',
            'pin_code'
        ]);
        $inputs['id'] = (int)$id;
        $validator    = new UpdatingPurchasingAccountValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        return $this->response()->success(
            [
                'purchasing_account' => Service::purchasingManager()->updatePurchasingAccount($validator->getPurchasingAccount(), $inputs, $validator->getAccessToken(), $this->user),
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id)
    {
        $inputs['id'] = (int)$id;
        $validator    = new DeletingPurchasingAccountValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        return $this->response()->success(
            [
                'purchasing_account' => Service::purchasingManager()->deletePurchasingAccount($validator->getPurchasingAccount(), $this->user),
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function balance($id)
    {
        $inputs['id'] = (int)$id;
        $validator    = new BalancingPurchasingAccountValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        return $this->response()->success(
            [
                'balance' => $validator->getBalance(),
            ]
        );
    }

    /**
     * @return JsonResponse
     */
    public function suggest()
    {
        $assigned               = $this->requests->get('assigned', false);
        $purchasingAccountQuery = PurchasingAccount::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'status' => PurchasingAccount::STATUS_ACTIVE
        ]);
        if ($assigned) {
            $purchasingAccountQuery->whereIn('merchant_id', $this->user->merchants->pluck('id'));
        }
        return $this->response()->success(
            ['purchasing_accounts' => $purchasingAccountQuery->get()]
        );
    }

}
