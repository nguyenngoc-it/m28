<?php

namespace Modules\Stock\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Gobiz\Database\DBHelper;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ExportStockLogs
{
    /** @var User $user */
    protected $user;
    protected $filter;

    /**
     * @var Builder
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
        $this->filter  = $filter;
        $this->builder = Service::stock()->listStockLogs($this->filter, $user);
    }

    function stockGenerator()
    {
        $results = DBHelper::chunkByIdGenerator($this->builder, 100, 'stock_logs.id', 'id');
        foreach ($results as $stockLogs) {
            /** @var StockLog $stockLog */
            foreach ($stockLogs as $stockLog) {
                yield $this->makeRow($stockLog);
            }
        }
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
        return (new FastExcel($this->stockGenerator()))->export('stock-logs-export.xlsx');
    }

    protected function makeRow(StockLog $stockLog)
    {
        $reason          = $this->makeReasonMessage($stockLog);
        $action          = $this->makeActionMessage($stockLog);
        $changeTemporary = $this->makeChangeMessage($stockLog, 'temporary');
        $changeCurrent   = $this->makeChangeMessage($stockLog, 'current');
        return [
            trans('created_at') => $stockLog->created_at->format('Y-m-d H:i:s'),
            trans('action') => $action,
            //trans('change') . ' ' . trans('temporary_stock') => $changeTemporary,
            trans('change') . ' ' . trans('current_stock') => $changeCurrent,
            trans('note') => $reason
        ];
    }

    /**
     * @param StockLog $stockLog
     * @return array|Translator|string|null
     */
    protected function makeActionMessage(StockLog $stockLog)
    {
        switch ($stockLog->action) {
            case Stock::ACTION_IMPORT:
                return trans('import');
            case Stock::ACTION_EXPORT:
                return trans('export');
            case Stock::ACTION_RESERVE:
                return trans('hold_for_order');
            case Stock::ACTION_EXPORT_FOR_ORDER:
                return trans('export_for_order');
            case Stock::ACTION_UNRESERVE:
                return trans('unhold_for_order');
            case Stock::ACTION_UNRESERVE_BY_ERROR:
                return trans('unhold_for_error');
            case Stock::ACTION_RESERVE_BY_ERROR:
                return trans('hold_for_error');
        }
        return '';
    }

    /**
     * @param StockLog $stockLog
     * @param string $string
     * @return int
     */
    protected function makeChangeMessage(StockLog $stockLog, string $string)
    {
        if ($string == 'temporary' && in_array($stockLog->action, Stock::$temporaryActions)) {
            if ($stockLog->quantity) {
                return ($stockLog->change == StockLog::CHANGE_INCREASE) ? $stockLog->quantity : -$stockLog->quantity;
            }
        }

        if ($string == 'current' && in_array($stockLog->action, Stock::$currentActions)) {
            if ($stockLog->real_quantity) {
                return ($stockLog->change == StockLog::CHANGE_INCREASE) ? $stockLog->real_quantity : -$stockLog->real_quantity;
            }
        }
        return 0;
    }

    protected function makeReasonMessage(StockLog $stockLog)
    {
        switch ($stockLog->action) {
            case Stock::ACTION_IMPORT:
                return $this->makeReasonImportMessage($stockLog);
            case Stock::ACTION_EXPORT:
            case Stock::ACTION_EXPORT_FOR_ORDER:
                return $this->makeReasonExportMessage($stockLog);
            case Stock::ACTION_RESERVE:
            case Stock::ACTION_RESERVE_BY_ERROR:
                return $this->makeReasonReserveMessage($stockLog);
            case Stock::ACTION_UNRESERVE:
            case Stock::ACTION_UNRESERVE_BY_ERROR:
                return $this->makeReasonUnReserveMessage($stockLog);
        }
        return '';
    }

    /**
     * @param StockLog $stockLog
     * @return string
     */
    private function makeReasonImportMessage(StockLog $stockLog)
    {
        $payload = $stockLog->payload;
        switch ($stockLog->object_type) {
            case StockLog::OBJECT_PURCHASING_PACKAGE:
                return 'Chứng từ nhập ' . Arr::get($payload, 'document.code') . ', kiện nhập ' . Arr::get($payload, 'purchasing_package.code');
            case StockLog::OBJECT_ORDER:
                return 'Chứng từ nhập hoàn ' . Arr::get($payload, 'document.code') . ', đơn hoàn ' . Arr::get($payload, 'order.code');
            case StockLog::OBJECT_SKU:
                return 'Chứng từ nhập ' . Arr::get($payload, 'document.code') . ', sku ' . Arr::get($payload, 'sku.code');
            case StockLog::OBJECT_DOCUMENT_SKU_INVENTORY:
                $explain = Arr::get($payload, 'explain');
                return 'Chứng từ kiểm kê ' . Arr::get($payload, 'document.code') . ($explain ? ', ' . $explain : '');
        }
        return '';
    }

    /**
     * @param StockLog $stockLog
     * @return string
     */
    private function makeReasonExportMessage(StockLog $stockLog)
    {
        $payload = $stockLog->payload;
        if ($stockLog->action == Stock::ACTION_EXPORT) {
            $explain = Arr::get($payload, 'explain');
            return 'Chứng từ kiểm kê ' . Arr::get($payload, 'document.code') . ($explain ? ', ' . $explain : '');
        }
        if ($stockLog->action == Stock::ACTION_EXPORT_FOR_ORDER) {
            return 'Chứng từ xuất ' . Arr::get($payload, 'document.code') . ', đơn xuất ' . Arr::get($payload, 'order.code');
        }
        return '';
    }

    /**
     * @param StockLog $stockLog
     * @return string
     */
    private function makeReasonReserveMessage(StockLog $stockLog)
    {
        $payload = $stockLog->payload;
        if ($stockLog->action == Stock::ACTION_RESERVE) {
            return 'Trừ tồn đơn ' . Arr::get($payload, 'order.code');
        }

        return '';
    }

    private function makeReasonUnReserveMessage(StockLog $stockLog)
    {
        $payload = $stockLog->payload;
        if ($stockLog->action == Stock::ACTION_UNRESERVE) {
            return 'Cộng tồn đơn ' . Arr::get($payload, 'order.code');
        }

        return '';
    }
}
