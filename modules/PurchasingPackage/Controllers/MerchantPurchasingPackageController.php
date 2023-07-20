<?php

namespace Modules\PurchasingPackage\Controllers;

use App\Base\Controller;
use App\Base\Validator;
use Illuminate\Http\JsonResponse;
use Modules\PurchasingPackage\Commands\MerchantCreatePurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Transformers\PurchasingPackageDetailTransformer;
use Modules\PurchasingPackage\Validators\DetailMerchantPurchasingPackageValidator;
use Modules\PurchasingPackage\Validators\MerchantCreatePurchasingPackageValidator;
use Modules\PurchasingPackage\Validators\ToTransportingMerchantPurchasingPackageValidator;
use Modules\Service;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MerchantPurchasingPackageController extends Controller
{
    /**
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs = $inputs ?: [
            'code',
            'freight_bill_code',
            'destination_warehouse_id',
            'shipping_partner_id',
            'status',
            'sku_code',
            'created_at',
            'imported_at',
            'sort',
            'sortBy',
            'page',
            'per_page',
            'paginate'
        ];

        $filter                = $this->requests->only($inputs);
        $filter                = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id']   = $this->user->tenant_id;
        $filter['merchant_id'] = $this->user->merchant->id;
        $filter['is_putaway']  = true;

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();
        return $this->response()->success(Service::purchasingPackage()->listing($filter, $this->user));
    }


    /**
     * @return BinaryFileResponse
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function export()
    {
        $filter   = $this->getQueryFilter();
        $pathFile = Service::purchasingPackage()->merchantExport($filter, $this->user);

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }


    /**
     * @return JsonResponse
     */
    public function create()
    {
        $input     = $this->request()->all();
        $validator = (new MerchantCreatePurchasingPackageValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $purchasingPackage = (new MerchantCreatePurchasingPackage(
            $input,
            $validator->getDestinationWarehouse(),
            $validator->getPackageItems(),
            $this->user,
            $validator->getShippingPartner()
        )
        )->handle();

        return $this->response()->success($purchasingPackage);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new DetailMerchantPurchasingPackageValidator(array_merge($this->requests->all(), ['id' => $id]));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $merchantPurchasingPackage = $validator->getMerchantPurchasingPackage();

        return $this->response()->success((new PurchasingPackageDetailTransformer())->transform($merchantPurchasingPackage));
    }

    /**
     * Chuyển trạng thái kiện từ init sang transporting
     *
     * JsonResponse
     */
    public function toTransporting()
    {
        $input     = $this->requests->only([
            'purchasing_package_ids',
        ]);
        $validator = new ToTransportingMerchantPurchasingPackageValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $purchasingPackages = $validator->getPurchasingPackages();
        /** @var PurchasingPackage $purchasingPackage */
        foreach ($purchasingPackages as $purchasingPackage) {
            Service::purchasingPackage()->changeState($purchasingPackage, PurchasingPackage::STATUS_TRANSPORTING, $this->user);
        }

        return $this->response()->success(['purchasing_packages' => $purchasingPackages]);
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function cancel($id)
    {
        $purchasingPackage = $this->user->merchant->purchasingPackages()->find($id);
        if (!$purchasingPackage instanceof PurchasingPackage) {
            return $this->response()->error('INPUT_INVALID', ['purchasing_package' => Validator::ERROR_INVALID]);
        }

        if($purchasingPackage->status != PurchasingPackage::STATUS_INIT) {
            return $this->response()->error('INPUT_INVALID', ['status' => Validator::ERROR_INVALID]);
        }

        $purchasingPackage->changeStatus(PurchasingPackage::STATUS_CANCELED, $this->user);

        return $this->response()->success($purchasingPackage);
    }
}
