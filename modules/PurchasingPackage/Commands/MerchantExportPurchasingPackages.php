<?php

namespace Modules\PurchasingPackage\Commands;

use Illuminate\Database\Eloquent\Collection;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class MerchantExportPurchasingPackages
{
    /** @var User $user */
    protected $user;
    protected $filter;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;

    /**
     * ExportStocks constructor.
     * @param array $filter
     * @param User $user
     */
    public function __construct(array $filter, User $user)
    {
        $this->user                  = $user;
        $this->filter                = $filter;
        $this->filter['export_data'] = true;
        $this->builder               = Service::purchasingPackage()->listing($this->filter, $user);
    }


    /**
     * @return string
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function handle()
    {
        $data = [];
        $this->builder->chunk(100, function (Collection $purchasingPackages) use (&$data) {
            foreach ($purchasingPackages as $purchasingPackage) {
                $destinationWarehouse   = $purchasingPackage->destinationWarehouse;
                $purchasingPackageItems = $purchasingPackage->purchasingPackageItems;
                foreach ($purchasingPackageItems as $purchasingPackageItem) {
                    $data[] = [
                        trans('package_code') => $purchasingPackage->code,
                        trans('tracking_number') => $purchasingPackage->freight_bill_code,
                        trans('warehouse') => ($destinationWarehouse) ? $destinationWarehouse->code : '',
                        trans('sku') => $purchasingPackageItem->sku->code,
                        trans('quantity_by_SKU') => $purchasingPackageItem->quantity,
                        trans('quantity_received_by_SKU') => $purchasingPackageItem->received_quantity,
                        trans('created_at') => $purchasingPackage->created_at->toDateTimeString(),
                        trans('imported_at') => ($purchasingPackage->imported_at) ? $purchasingPackage->imported_at->toDateTimeString() : '',
                        trans('package_status') => trans('purchasing_package.status.' . $purchasingPackage->status),
                    ];
                }
            }
        });
        $list = collect($data);
        return (new FastExcel($list))->export('merchant-export-purchasing-packages.xlsx');
    }
}
