<?php

namespace Modules\PurchasingManager\Controllers\Api\V1;

use App\Base\Controller;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingManager\Models\PurchasingAccount;
use Modules\PurchasingManager\Models\PurchasingService;
use Modules\PurchasingManager\Services\PurchasingAccountEvent;
use Modules\PurchasingManager\Transformers\PurchasingAccountTransformerNew;
use Modules\PurchasingManager\Transformers\PurchasingServiceTransformer;
use Modules\PurchasingManager\Validators\CreatingMerchantPurchasingAccountApiValidator;
use Modules\PurchasingManager\Validators\DeletingPurchasingAccountValidator;
use Modules\Service;

class MerchantPurchasingAccountApiController extends Controller
{
    public function index()
    {
        $request      = $this->request()->all();
        $status       = data_get($request, 'status');
        $merchantCode = data_get($request, 'merchant_code');
        $perPage      = data_get($request, 'per_page');
        if (is_string($status)) {
            $status = [$status];
        }
        $merchant   = Merchant::query()->where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();
        $dataReturn = [];
        if ($merchant){
            $paginator = PurchasingAccount::query()->select('purchasing_accounts.*')
                ->where('merchant_id', $merchant->id)
                // ->whereIn('status', $status)
                ->orderBy('id')
                ->paginate($perPage);
            $purchasingAccount = $paginator->getCollection();
            $include = data_get($request,'include');
            $fractal = new FractalManager();
            if ($include){
                $fractal->parseIncludes($include);
            }
            $resource = new FractalCollection($purchasingAccount, new PurchasingAccountTransformerNew());
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);
    }

    /**
     * Lấy danh sách purchasing services
     *
     * @return void
     */
    public function purchasingServices()
    {
        $request      = $this->request()->all();
        $merchantCode = data_get($request, 'merchant_code');
        $perPage      = data_get($request, 'per_page');

        $merchant   = Merchant::query()->where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();
       
        $dataReturn = [];
        if ($merchant){
            $paginator = PurchasingService::query()
                                            ->where('tenant_id', $merchant->tenant_id)
                                            ->orderBy('id')
                                            ->paginate($perPage);

            $purchasingServices = $paginator->getCollection();

            $include = data_get($request,'include');
            $fractal = new FractalManager();
            if ($include){
                $fractal->parseIncludes($include);
            }
            $resource = new FractalCollection($purchasingServices, new PurchasingServiceTransformer());
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);
    }

    /**
     * @return JsonResponse
     */
    public function create()
    {
        $inputs = $this->requests->only([
            'purchasing_service_id',
            'username',
            'password',
            'pin_code',
        ]);

        $request      = $this->request()->all();
        $merchantCode = data_get($request, 'merchant_code');

        $merchant   = Merchant::query()->where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();
       
        $dataReturn = [];

        if ($merchant) {

            $inputs['merchant_id'] = $merchant->id;
            $validator = new CreatingMerchantPurchasingAccountApiValidator($inputs);
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
                $purchasingAccount = $deletePurchasingAccount;
            } else {
                $purchasingAccount = Service::purchasingManager()->createPurchasingAccount($inputs, $validator->getAccessToken(), $this->user);
            }

            $fractal  = new FractalManager();
            $resource = new FractalItem($purchasingAccount, new PurchasingAccountTransformerNew);
            $dataReturn = $fractal->createData($resource)->toArray();

        } else {
            $dataReturn = [
                'message' => 'Merchant ' . $merchantCode . ' Not Exist'
            ];
            return $this->response()->success($dataReturn);
        }

        return $this->response()->success($dataReturn);
    }

    /**
     * Xoá tk mua hộ 
     *
     * @param [type] $id
     * @return void
     */
    public function delete($id)
    {
        $inputs['id'] = (int) $id;

        $request      = $this->request()->all();
        $merchantCode = data_get($request, 'merchant_code');

        $merchant   = Merchant::query()->where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();
       
        $dataReturn = [];

        if ($merchant) {
            $inputs['merchant_id'] = $merchant->id;
            $validator    = new DeletingPurchasingAccountValidator($inputs);
            if ($validator->fails()) {
                return $this->response()->error($validator);
            }

            $deleted = Service::purchasingManager()->deletePurchasingAccount($validator->getPurchasingAccount(), $this->user);

            if ($deleted) {
                $dataReturn = [
                    'message' => 'Deleted success'
                ];
            } else {
                $dataReturn = [
                    'message' => 'Cant delete'
                ];
                return $this->response()->error($dataReturn);
            }      
        } else {
            $dataReturn = ['message' => 'Merchant not found'];
            return $this->response()->error($dataReturn);
        }

        return $this->response()->success($dataReturn);
    }

}
