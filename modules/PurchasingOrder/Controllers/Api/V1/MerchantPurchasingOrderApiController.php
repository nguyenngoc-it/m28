<?php

namespace Modules\PurchasingOrder\Controllers\Api\V1;

use App\Base\Controller;
use App\Base\Validator;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Transformers\PurchasingOrderTransformerNew;
use Modules\PurchasingOrder\Transformers\PurchasingVariantTransformerNew;
use Modules\PurchasingOrder\Validators\MerchantPurchasingOrderMappingVariantValidator;
use Modules\PurchasingOrder\Validators\UpdateMerchantPurchasingOrderValidator;
use Modules\Service;

class MerchantPurchasingOrderApiController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $request             = $this->request()->all();
        $code                = data_get($request, 'code');
        $merchantCode        = data_get($request, 'merchant_code');
        $purchasingAccountId = data_get($request, 'purchasing_account_id');
        $perPage             = data_get($request, 'per_page');
        $hasPackage          = data_get($request, 'has_package', true);
        $isPutaway           = data_get($request, 'is_putaway', false);

        $merchant   = Merchant::query()->where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();
        $dataReturn = [];
        if ($merchant) {
            $paginator       = PurchasingOrder::query()->select('purchasing_orders.*')
                ->code($code)
                ->purchasingAccountId($purchasingAccountId)
                ->hasPackage($hasPackage)
                ->isPutaway($isPutaway)
                ->paginate($perPage);
            $purchasingOrder = $paginator->getCollection();
            $include         = data_get($request, 'include');
            $fractal         = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalCollection($purchasingOrder, new PurchasingOrderTransformerNew());
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id)
    {
        $request      = $this->request()->all();
        $merchantCode = data_get($request, 'merchant_code');
        $merchant     = Merchant::query()->where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();
        $dataReturn   = [];
        if ($merchant) {
            $purchasingOrder = PurchasingOrder::query()
                ->where('id', $id)
                ->where('merchant_id', $merchant->id)
                ->first();
            if (!$purchasingOrder) {
                return $this->response()->error('ERROR_NOT_EXIST', ['purchasing_orders' => Validator::ERROR_EXISTS]);
            }
            $include = data_get($request, 'include');
            $fractal = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource   = new FractalItem($purchasingOrder, new PurchasingOrderTransformerNew());
            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);
    }

    /**
     * @param $id
     * @param $itemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function mapping($id, $itemId)
    {

        $inputs            = $this->requests->only(['sku_id', 'include', 'merchant_code']);
        $inputs['id']      = (int)$id;
        $inputs['item_id'] = (int)$itemId;
        $merchantCode = data_get($inputs, 'merchant_code');
        $validator         = new MerchantPurchasingOrderMappingVariantValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $merchant     = Merchant::query()->where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();
        $dataReturn = [];
        if ($merchant){
            $purchasingVariant = $validator->getPurchasingVariant();

            Service::purchasingOrder()->mappingVariant($validator->getPurchasingOrder(), $purchasingVariant, $validator->getSku(), $this->user);

            $purchasingVariant = $purchasingVariant->refresh();
            $include = data_get($inputs, 'include');
            $fractal = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource   = new FractalItem($purchasingVariant, new PurchasingVariantTransformerNew());
            $dataReturn = $fractal->createData($resource)->toArray();
        }

        return $this->response()->success($dataReturn);
    }

    public function update($id)
    {
        $input        = $this->requests->only([
            'services',
            'warehouse_id',
            'is_putaway',
            'merchant_code',
            'include'
        ]);
        $merchantCode = data_get($input, 'merchant_code');
        $validator    = new UpdateMerchantPurchasingOrderValidator(array_merge($input, ['id' => $id]));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $merchant   = Merchant::query()->where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();
        $dataReturn = [];
        if ($merchant) {
            $purchasingOrder = Service::purchasingOrder()->updateMerchantPurchasingOrder($validator->getPurchasingOrder(), $input, $this->user);
            $include         = data_get($input, 'include');
            $fractal         = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource   = new FractalItem($purchasingOrder, new PurchasingOrderTransformerNew);
            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);
    }

}
