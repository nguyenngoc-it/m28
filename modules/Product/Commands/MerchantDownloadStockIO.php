<?php

namespace Modules\Product\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Gobiz\Database\DBHelper;
use Illuminate\Support\Arr;
use Jenssegers\Mongodb\Eloquent\Builder;
use Modules\Order\Models\Order;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;
use Generator;

class MerchantDownloadStockIO
{
    /** @var User $user */
    protected $user;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var array
     */
    protected $filter;

    /**
     * @var array
     */
    protected $session;

    /**
     * ExportOrder constructor.
     * @param array $filter
     * @param User $user
     */
    public function __construct(array $filter, User $user)
    {
        $this->user    = $user;
        $this->session = Arr::pull($filter, 'session', []);
        $this->filter  = $filter;
        $productIds    = ProductMerchant::query()->where('merchant_id', $this->user->merchant->id)
            ->pluck('product_id')->toArray();

        $this->filter['product_id'] = $productIds;
        $this->builder              = Service::product()->skuQuery($this->filter)->with([
            'stocks'
        ]);
    }

    /**
     * @return Generator
     */
    public function makeGenerator()
    {
        /**
         * @var Order $order
         */
        $results = DBHelper::chunkByIdGenerator($this->builder, 100, 'skus.id', 'id');
        foreach ($results as $skus) {
            /** @var Sku $sku */
            foreach ($skus as $sku) {
                /** @var Stock $stock */
                foreach ($sku->stocks as $stock) {
                    yield $this->makeRow($sku, $stock);
                }
            }
        }
    }

    /**
     * @param Sku $sku
     * @param Stock $stock
     * @return array
     */
    protected function makeRow(Sku $sku, Stock $stock)
    {
        return [
            trans('product_name') => $sku->name,
            trans('sku') => $sku->code,
            trans('warehouse') => $stock->warehouse->name,
            trans('warehouse_area') => $stock->warehouseArea->name,
            trans('inventory') . ' ' . trans('begin_session') => $this->getStockSession($stock, $this->session['from']),
            trans('inventory') . ' ' . trans('end_session') => $this->getStockSession($stock, $this->session['to']),
            trans('import') . ' ' . trans('on_session') => $this->getStockOnSession($stock, StockLog::CHANGE_INCREASE),
            trans('export') . ' ' . trans('on_session') => $this->getStockOnSession($stock, StockLog::CHANGE_DECREASE),
            trans('status') => $sku->status == Sku::STATUS_ON_SELL ? 'Đang bán' : 'Ngừng bán',
        ];
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
        return (new FastExcel($this->makeGenerator()))->export('seller-stocks.xlsx');
    }

    /**
     * @param Stock $stock
     * @param string $fromDate
     * @return int
     */
    protected function getStockSession(Stock $stock, string $fromDate)
    {
        /** @var StockLog|null $stockLog */
        $stockLog = StockLog::query()->where('stock_id', $stock->id)
            ->where('created_at', '<', $fromDate)
            ->orderBy('created_at', 'desc')->first();
        return $stockLog ? (int)$stockLog->stock_quantity : 0;
    }

    /**
     * @param Stock $stock
     * @param string $change
     * @return int
     */
    protected function getStockOnSession(Stock $stock, string $change)
    {
        return (int)StockLog::query()->where('stock_id', $stock->id)
            ->where('change', $change)->whereBetween('created_at', $this->session)->sum('real_quantity');
    }

}
