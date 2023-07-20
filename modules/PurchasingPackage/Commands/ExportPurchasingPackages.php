<?php

namespace Modules\PurchasingPackage\Commands;


use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\PurchasingPackage\Models\PurchasingPackageService;
use Modules\Service;
use Modules\User\Models\User;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Rap2hpoutre\FastExcel\FastExcel;
use Generator;
use Gobiz\Database\DBHelper;

class ExportPurchasingPackages
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
        $this->user    = $user;
        $this->filter  = $this->makeFilter($filter);
    }

    /**
     * @param $filter
     * @return mixed
     */
    protected function makeFilter($filter)
    {
        $page       = Arr::pull($filter, 'page', config('paginate.page'));
        $perPage    = Arr::pull($filter, 'per_page', config('paginate.per_page'));
        $paginate   = Arr::pull($filter, 'paginate', true);
        $skuCode    = Arr::pull($filter, 'sku_code');
        $sortBy     = Arr::pull($filter, 'sort_by', 'id');
        $sort       = Arr::pull($filter, 'sort', 'desc');

        if ($skuCode) {
            $skuIds           = $this->user->tenant->skus()->where('code', $skuCode)->pluck('id')->toArray();
            $filter['sku_id'] = $skuIds;
        }

        return $filter;
    }

    /**
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function handle()
    {
        return (new FastExcel($this->makeGenerator()))->export('/tmp/purchasing-packages-export-'.$this->user->id.'.xlsx');
    }


    /**
     * @return Generator
     */
    public function makeGenerator()
    {
        /**
         * @var Order $order
         */
        $results = DBHelper::chunkByIdGenerator($this->makeQuery(), 200);

        foreach ($results as $purchasingPackages) {
            /** @var PurchasingPackage $purchasingPackage */
            foreach ($purchasingPackages as $purchasingPackage) {
                $purchasingPackageServices = $purchasingPackage->purchasingPackageServices;
                foreach ($purchasingPackageServices as $purchasingPackageService) {
                    yield $this->makeRow($purchasingPackage, $purchasingPackageService);
                }
            }
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function makeQuery()
    {
        return Service::purchasingPackage()->query($this->filter)
            ->with([
                'merchant',
                'purchasingPackageServices',
                'purchasingPackageServices.servicePrice',
                'purchasingPackageServices.servicePrice.service'
            ])
            ->getQuery();
    }

    /**
     * @param PurchasingPackage $purchasingPackage
     * @param PurchasingPackageService $purchasingPackageService
     * @return array
     */
    protected function makeRow(PurchasingPackage $purchasingPackage, PurchasingPackageService $purchasingPackageService)
    {
        $servicePrice = $purchasingPackageService->servicePrice;
        $service      = $servicePrice->service;
        $serviceName  = ($service instanceof Service) ? $service->name : '';

        $skus = '';
        foreach ($purchasingPackageService->skus as $sku) {
            $skus .= (isset($sku['sku_name'])) ? $sku['sku_name'].'('.$sku['sku_code'].')' ." - " : " ";
        }

        return [
            trans('seller_name') => ($purchasingPackage->merchant) ? $purchasingPackage->merchant->name : '',
            trans('package_code') => $purchasingPackage->code,
            trans('service') => $serviceName .' ('. $servicePrice->label.')',
            trans('price') => $purchasingPackageService->price,
            trans('quantity') => $purchasingPackageService->quantity,
            trans('amount') => $purchasingPackageService->amount,
            trans('sku_apply') => $skus,
            trans('package_status') => trans('purchasing_package.status.'.$purchasingPackage->status),
            trans('finance_status') => trans('purchasing_package.finance_status.'.$purchasingPackage->finance_status),
            trans('imported_at') => ($purchasingPackage->imported_at) ? $purchasingPackage->imported_at->toDateTimeString() : '',
        ];
    }
}
