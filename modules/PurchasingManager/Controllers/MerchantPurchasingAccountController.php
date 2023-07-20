<?php

namespace Modules\PurchasingManager\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\PurchasingManager\Models\PurchasingAccount;
use Modules\PurchasingManager\Services\PurchasingAccountEvent;
use Modules\PurchasingManager\Validators\CreatingMerchantPurchasingAccountValidator;
use Modules\PurchasingManager\Validators\DeletingMerchantPurchasingAccountValidator;
use Modules\PurchasingManager\Validators\DeletingPurchasingAccountValidator;
use Modules\Service;

class MerchantPurchasingAccountController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $status = $this->requests->get('status', []);
        if (is_string($status)) {
            $status = [$status];
        }
        $results = Service::purchasingManager()->listPurchasingAccountsByMerchant($this->user->merchant, $status);

        return $this->response()->success([
            'purchasing_accounts' => $results
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function create()
    {
        $inputs                = $this->requests->only([
            'purchasing_service_id',
            'username',
            'password',
            'pin_code',
        ]);
        $inputs['merchant_id'] = $this->user->merchant->id;
        $validator             = new CreatingMerchantPurchasingAccountValidator($inputs);
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
    public function delete($id)
    {
        $inputs['id'] = (int)$id;
        $validator    = new DeletingMerchantPurchasingAccountValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        return $this->response()->success(
            [
                'purchasing_account' => Service::purchasingManager()->deletePurchasingAccount($validator->getPurchasingAccount(), $this->user),
            ]
        );
    }
}
