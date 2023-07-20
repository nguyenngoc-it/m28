<?php

namespace Modules\PurchasingPackage\Controllers\Api\V1;

use App\Base\Controller;
use App\Base\Validator;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingOrder\Transformers\PurchasingOrderTransformerNew;
use Modules\PurchasingOrder\Validators\UpdateMerchantPurchasingOrderValidator;
use Modules\PurchasingPackage\Commands\MerchantCreatePurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Transformers\PurchasingPackageTransformerNew;
use Modules\PurchasingPackage\Validators\MerchantCreatePurchasingPackageValidatorNew;
use Modules\Service;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class MerchantPurchasingPackageApiController extends Controller
{

    public function index()
    {
        $request                = $this->request()->all();
        $code                   = data_get($request, 'code');
        $skuCode                = data_get($request, 'sku_code');
        $freightBillCode        = data_get($request, 'freight_bill_code');
        $destinationWarehouseId = data_get($request, 'destination_warehouse_id');
        $shippingPartnerId      = data_get($request, 'shipping_partner_id');
        $status                 = data_get($request, 'status');
        $sortBy                 = data_get($request, 'sort_by', 'created_at');
        $sortByValue            = data_get($request, 'sort_by_value', 'DESC');
        $createdAt              = [
            'from' => data_get($request, 'created_from'),
            'to' => data_get($request, 'created_to'),
        ];
        $merchantCode           = data_get($request, 'merchant_code');
        $perPage                = data_get($request, 'per_page', 20);

        $sortByAvaiable = [
            'created_at'  => 'created_at',
            'updated_at'  => 'updated_at',
            'imported_at' => 'imported_at',
        ];

        $sortByValueAvaiable = [
            'DESC' => 'DESC',
            'ASC'  => 'ASC',
        ];

        $sortBy      = data_get($sortByAvaiable, $sortBy, 'created_at');
        $sortByValue = data_get($sortByValueAvaiable, $sortByValue, 'DESC');

        $skuIds = '';
        if ($skuCode) {
            $skuIds            = $this->user->tenant->skus()->where('code', $skuCode)->pluck('id')->toArray();
            $request['sku_id'] = $skuIds;
            unset($request['sku_code']);
        }
        // chỉ áp dụng đối với những merchant cùng tenant và tồn tại trên m28
        $merchant = Merchant::query()->where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        $dataReturn = [];
        if ($merchant) {
            $paginator         = PurchasingPackage::query()->select('purchasing_packages.*')
                ->Code($code)
                ->SkuCode($skuIds)
                ->FreightBillCode($freightBillCode)
                ->Warehouse($destinationWarehouseId)
                ->ShippingPartner($shippingPartnerId)
                ->Status($status)
                ->CreatedAt($createdAt)
                ->where('merchant_id', $merchant->id)
                ->orderBy('purchasing_packages.' . $sortBy, $sortByValue)
                ->paginate($perPage);
            $purchasingPackage = $paginator->getCollection();
            $include           = data_get($request, 'include');
            $fractal           = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalCollection($purchasingPackage, new PurchasingPackageTransformerNew);
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

            $dataReturn = $fractal->createData($resource)->toArray();
        }

        return $this->response()->success($dataReturn);
    }

    public function detail($id)
    {
        $request      = $this->request()->all();
        $merchantCode = data_get($request, 'merchant_code');

        $merchant = Merchant::query()->where('code', $merchantCode)
            ->where('tenant_id', $this->user->tenant_id)->first();

        $dataReturn = [];
        if ($merchant) {
            $purchasingPackage = PurchasingPackage::query()->select('purchasing_packages.*')
                ->where('id', $id)
                ->where('merchant_id', $merchant->id)
                ->first();
            if (!$purchasingPackage) {
                return $this->response()->error('ERROR_NOT_EXIST', ['purchasing_package' => Validator::ERROR_NOT_EXIST]);
            }
            $include = data_get($request, 'include');
            $fractal = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource   = new FractalItem($purchasingPackage, new PurchasingPackageTransformerNew);
            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);
    }

    /** Huỷ kiện theo merchant
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($id)
    {
        $request      = $this->request()->all();
        $merchantCode = data_get($request, 'merchant_code');

        $merchant = Merchant::query()->where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();
        if (!$merchant) {
            return $this->response()->error('ERROR_NOT_EXIST', ['merchant' => Validator::ERROR_NOT_EXIST]);
        }

        $purchasingPackage = $merchant->purchasingPackages()->find($id);
        if (!$purchasingPackage instanceof PurchasingPackage) {
            return $this->response()->error('ERROR_NOT_EXIST', ['purchasing_package' => Validator::ERROR_NOT_EXIST]);
        }
        if ($purchasingPackage->status != PurchasingPackage::STATUS_INIT) {
            return $this->response()->error('INPUT_INVALID', ['status' => Validator::ERROR_INVALID]);
        }
        $purchasingPackage->changeStatus(PurchasingPackage::STATUS_CANCELED, $this->user);

        $include = data_get($request, 'include');
        $fractal = new FractalManager();
        if ($include) {
            $fractal->parseIncludes($include);
        }
        $resource   = new FractalItem($purchasingPackage, new PurchasingPackageTransformerNew);
        $dataReturn = $fractal->createData($resource)->toArray();
        return $this->response()->success($dataReturn);
    }

    public function create()
    {
        $input        = $this->request()->all();
        $merchantCode = data_get($input, 'merchant_code');
        $validator    = (new MerchantCreatePurchasingPackageValidatorNew($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $merchant   = Merchant::query()->where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();
        $dataReturn = [];

        if ($merchant) {
            $purchasingPackage = (new MerchantCreatePurchasingPackage(
                $input,
                $validator->getDestinationWarehouse(),
                $validator->getPackageItems(),
                $this->user,
                $validator->getShippingPartner()
            )
            )->handle();

            $include = data_get($input, 'include');
            $fractal = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource   = new FractalItem($purchasingPackage, new PurchasingPackageTransformerNew);
            $dataReturn = $fractal->createData($resource)->toArray();
        }

        return $this->response()->success($dataReturn);
    }

    public function export()
    {
        $request = $this->request()->all();
        $merchantCode = data_get($request, 'merchant_code');
        if (!$merchantCode) {
            throw new BadRequestException('merchant_code_required');
        }

        $merchant = Merchant::query()->where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        if (!$merchant) {
            return $this->response()->error('ERROR_NOT_EXIST', ['merchant' => Validator::ERROR_NOT_EXIST]);
        }

        $inputs = [
            'code',
            'freight_bill_code',
            'destination_warehouse_id',
            'shipping_partner_id',
            'status',
            'sku_code',
            'created_at',
            'imported_at',
            'sort_by',
            'page',
            'per_page',
            'paginate'
        ];

        $filter = $this->requests->only($inputs);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['sort'] = data_get($filter, 'sort_by_value', 'desc');
        $filter['sort_by'] = data_get($filter, 'sort_by', 'created_at');
        $filter['created_at'] = [
            'from' => data_get($request, 'created_from'),
            'to' => data_get($request, 'created_to'),
        ];
        $filter['tenant_id']   = $this->user->tenant_id;
        $filter['merchant_id'] = $merchant->id;
        $filter['is_putaway']  = true;

        $pathFile = Service::purchasingPackage()->merchantExport($filter, $this->user);

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }
}
