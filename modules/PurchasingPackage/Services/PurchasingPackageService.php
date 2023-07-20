<?php

namespace Modules\PurchasingPackage\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Exception;
use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Document\Models\Document;
use Modules\PurchasingPackage\Commands\ExportPurchasingPackages;
use Modules\PurchasingPackage\Commands\ImportFinanceStatus;
use Modules\PurchasingPackage\Commands\MerchantExportPurchasingPackages;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;
use Modules\PurchasingPackage\Models\PurchasingPackageService as PurchasingPackageServiceModel;
use Modules\Service;
use Modules\User\Models\User;

class PurchasingPackageService implements PurchasingPackageServiceInterface
{

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user)
    {
        $page       = Arr::pull($filter, 'page', config('paginate.page'));
        $perPage    = Arr::pull($filter, 'per_page', config('paginate.per_page'));
        $paginate   = Arr::pull($filter, 'paginate', true);
        $sortBy     = Arr::pull($filter, 'sort_by', 'id');
        $sort       = Arr::pull($filter, 'sort', 'desc');
        $exportData = Arr::pull($filter, 'export_data', false);

        $query = Service::purchasingPackage()->query($filter)->getQuery();
        $query->orderBy('purchasing_packages' . '.' . $sortBy, $sort);
        $query->with(['purchasingPackageItems', 'destinationWarehouse', 'purchasingPackageServices', 'importingBarcodes']);

        if ($exportData) {
            return $query;
        }

        if (!$paginate) {
            return $query->get();
        }

        $results = $query->paginate($perPage, ['purchasing_packages.*'], 'page', $page);
        return [
            'purchasing_packages' => $results->items(),
            'pagination' => $results,
        ];
    }

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new PurchasingPackageQuery())->query($filter);
    }


    /**
     * @param PurchasingPackage $purchasingPackage
     * @param $status
     * @param User $creator
     * @return PurchasingPackage
     */
    public function changeState(PurchasingPackage $purchasingPackage, $status, User $creator)
    {
        if ($purchasingPackage->status == $status) {
            return $purchasingPackage;
        }

        $purchasingPackage->status = $status;
        $purchasingPackage->save();

        $purchasingPackage->logActivity(PurchasingPackageEvent::CHANGE_STATUS, $creator, $purchasingPackage->getChanges());

        return $purchasingPackage;
    }


    /**
     * @param PurchasingPackage $purchasingPackage
     * @param $status
     * @param User $creator
     * @return PurchasingPackage
     */
    public function updateFinanceStatus(PurchasingPackage $purchasingPackage, $status, User $creator)
    {
        if ($purchasingPackage->finance_status == $status) {
            return $purchasingPackage;
        }

        $purchasingPackage->finance_status = $status;
        $purchasingPackage->save();

        $purchasingPackage->logActivity(PurchasingPackageEvent::UPDATE_FINANCE_STATUS, $creator, $purchasingPackage->getChanges());

        return $purchasingPackage;
    }

    /**
     * @param PurchasingPackage $purchasingPackage
     * @param Document $document
     * @return PurchasingPackage
     */
    public function updateReceivedQuantityByDocument(PurchasingPackage $purchasingPackage, Document $document)
    {
        //Cập nhật số lượng thực nhận cho các PackageItems
        $totalReceivedQuantity  = 0;
        $purchasingPackageItems = $purchasingPackage->purchasingPackageItems()->get();
        $skuReceivedQuantity = [];
        /** @var PurchasingPackageItem $packageItem */
        foreach ($purchasingPackageItems as $packageItem) {
            $receivedQuantity               = $document->documentSkuImportings()->where('sku_id', $packageItem->sku_id)->sum('real_quantity');
            $packageItem->received_quantity = $receivedQuantity;
            $packageItem->save();

            $totalReceivedQuantity += $receivedQuantity;
            $skuReceivedQuantity[$packageItem->sku_id] = $receivedQuantity;
        }

        $purchasingPackage->received_quantity = $totalReceivedQuantity;
        $purchasingPackage->save();

        // Cập nhật số lượng trên dịch vụ kiện
        $purchasingPackageServices = $purchasingPackage->purchasingPackageServices()->get();
        /** @var PurchasingPackageServiceModel $purchasingPackageService */
        foreach ($purchasingPackageServices as $purchasingPackageService) {
            $totalReceivedQuantity = 0;
            $skus = $purchasingPackageService->skus;
            if(!empty($skus)) {
                foreach ($skus as $key => $sku) {
                    foreach ($skuReceivedQuantity as $skuId => $receivedQuantity) {
                        if($sku['sku_id'] == $skuId) {
                            $skus[$key]['quantity'] = $receivedQuantity;
                        }
                    }
                    $totalReceivedQuantity += isset($skus[$key]['quantity']) ? $skus[$key]['quantity'] : 0;
                }
            }
            if($totalReceivedQuantity > 0 && $purchasingPackageService->quantity != $totalReceivedQuantity) {
                $purchasingPackageService->quantity = $totalReceivedQuantity;
                $purchasingPackageService->skus = $skus;
                $purchasingPackageService->save();
            }
        }

        return $purchasingPackage;
    }

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function export(array $filter, User $user)
    {
        return (new ExportPurchasingPackages($filter, $user))->handle();
    }

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function merchantExport(array $filter, User $user)
    {
        return (new MerchantExportPurchasingPackages($filter, $user))->handle();
    }

    /**
     * @param $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function importFinanceStatus($filePath, User $creator)
    {
        return (new ImportFinanceStatus($filePath, $creator))->handle();
    }

    /**
     * Thêm sản phẩm cho kiện nhập
     *
     * @param PurchasingPackage $purchasingPackage
     * @param array $inputs
     * @return void
     */
    public function addItems(PurchasingPackage $purchasingPackage, array $inputs)
    {
        $packageItems = Arr::get($inputs, 'package_items', []);
        DB::transaction(function () use ($purchasingPackage, $packageItems) {
            foreach ($packageItems as $item) {
                PurchasingPackageItem::updateOrCreate(
                    [
                        'purchasing_package_id' => $purchasingPackage->id,
                        'sku_id' => $item['sku_id']
                    ],
                    [
                        'quantity' => $item['quantity']
                    ]
                );
            }
        });
    }
}
