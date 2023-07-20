<?php

namespace Modules\PurchasingPackage\Controllers;

use App\Base\Controller;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Exception;
use Illuminate\Http\JsonResponse;
use Modules\PurchasingPackage\Commands\CreatePurchasingPackage;
use Modules\PurchasingPackage\Commands\MerchantCreatePurchasingPackage;
use Modules\Auth\Services\Permission;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Validators\AddingItemPurchasingPackageValidator;
use Modules\PurchasingPackage\Validators\CreatePurchasingPackageValidator;
use Modules\PurchasingPackage\Validators\MerchantCreatePurchasingPackageValidator;
use Modules\Service;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Validator;

class PurchasingPackageController extends Controller
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
            'merchant_id',
            'status',
            'finance_status',
            'sku_code',
            'created_at',
            'imported_at',
            'sort',
            'sortBy',
            'page',
            'per_page',
            'paginate'
        ];

        $filter              = $this->requests->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;
        if (!$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT)) {
            $filter['supplier_id'] = $this->user->suppliers->pluck('id')->toArray();
        }

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
     * @return JsonResponse|BinaryFileResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function export()
    {
        $filter = $this->getQueryFilter();

        $pathFile = Service::purchasingPackage()->export($filter, $this->user);

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function importFinanceStatus()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user   = $this->getAuthUser();
        $path   = Service::product()->getRealPathFile($input['file']);
        $errors = Service::purchasingPackage()->importFinanceStatus($path, $user);

        return $this->response()->success(compact('errors'));
    }

    /**
     * @return JsonResponse
     */
    public function create()
    {
        $input     = $this->request()->all();
        $validator = (new CreatePurchasingPackageValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $purchasingPackage = (new CreatePurchasingPackage(
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
     * @param PurchasingPackage $purchasingPackage
     * @return JsonResponse
     */
    public function addItems(PurchasingPackage $purchasingPackage)
    {
        $inputs    = $this->request()->only(['package_items']);
        $validator = new AddingItemPurchasingPackageValidator($purchasingPackage, $inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        Service::purchasingPackage()->addItems($purchasingPackage, $inputs);
        return $this->response()->success(true);
    }
}
