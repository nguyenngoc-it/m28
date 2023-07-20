<?php

namespace Modules\OrderIntegration\Controllers;

use App\Base\Controller;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;
use Modules\OrderIntegration\Validators\AutoInspectingManualOrderValidator;
use Modules\OrderIntegration\Validators\ChangingStatusManualOrderValidator;
use Modules\OrderIntegration\Validators\CreateShopeeDocumentValidator;
use Modules\OrderIntegration\Validators\FixStockOrderValidator;
use Modules\OrderIntegration\Validators\UpdatingOrderPackingManualOrderValidator;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;
use Modules\Shopee\Jobs\ShopeeCreateShippingDocumentJob;
use \Illuminate\Support\Facades\Artisan;
use Modules\Merchant\Models\Merchant;
use Modules\OrderIntegration\Commands\UpdatePaymentData;
use Modules\Warehouse\Models\Warehouse;

class OrderManualFixController extends Controller
{

    /**
     * Đồng bộ số dư lịch sử thay đổi tồn
     *
     * @return JsonResponse
     */
    public function syncHistoryStockLog()
    {
        $input     = $this->request()->only(['tenant_id', 'seller_code']);
        $validator = Validator::make($input, [
            'tenant_id' => 'int',
            'seller_code' => 'string'
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        Artisan::call('running_man SyncHistoryStockLog --tenant_id=' . Arr::get($input, 'tenant_id') . '
         --seller_code=' . Arr::get($input, 'seller_code'));
        return $this->response()->success(1);
    }

    /**
     * Chạy lại phí tồn
     *
     * @return JsonResponse
     */
    public function storageFeeArrear()
    {
        $input     = $this->request()->only(['tenant_id', 'seller_code', 'between_days']);
        $validator = Validator::make($input, [
            'tenant_id' => 'required',
            'seller_code' => 'required',
            'between_days' => 'array|required'
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $betweenDays = Arr::get($input, 'between_days');
        Artisan::call('running_man storageFeeArrear --tenant_id=' . Arr::get($input, 'tenant_id') . '
         --seller_code=' . Arr::get($input, 'seller_code') . ' --from_day=' . $betweenDays[0] . ' --to_day=' . $betweenDays[1]);
        return $this->response()->success(1);
    }

    /**
     * Tự động chọn vị trí kho xuất cho đơn không quan tâm trạng thái đơn
     *
     * @return JsonResponse
     */
    public function autoInspection()
    {
        $input     = $this->request()->only(['tenant_code', 'order_codes']);
        $validator = (new AutoInspectingManualOrderValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $orders             = Order::query()->where('tenant_id', $validator->getTenant()->id)
            ->whereIn('code', $validator->getOrderCodes())->get();
        $unInspectionOrders = [];
        $inspectionOrders   = [];
        /** @var Order $order */
        foreach ($orders as $order) {
            if (Service::order()->autoInspection($order, $this->user)) {
                $inspectionOrders[] = $order->code;
            } else {
                $unInspectionOrders[] = $order->code;
            }
        }

        return $this->response()->success([
            'inspected' => $inspectionOrders,
            'un_inspected' => $unInspectionOrders
        ]);
    }

    /**
     * Bỏ chọn vị trí kho xuất cho đơn không quan tâm trạng thái đơn
     *
     * @return JsonResponse
     */
    public function removeStock()
    {
        $input     = $this->request()->only(['tenant_code', 'order_codes']);
        $validator = (new AutoInspectingManualOrderValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $orders = Order::query()->where('tenant_id', $validator->getTenant()->id)
            ->whereIn('code', $validator->getOrderCodes())->get();
        /** @var Order $order */
        foreach ($orders as $order) {
            Service::order()->removeStockOrder($order, $this->user);
        }

        return $this->response()->success();
    }

    /**
     * Cập nhật lại thông tin của orderPacking
     *
     * @return JsonResponse
     */
    public function updateOrderPacking()
    {
        $input     = $this->request()->only(['tenant_code', 'order_codes']);
        $validator = (new UpdatingOrderPackingManualOrderValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $orders               = Order::query()->where('tenant_id', $validator->getTenant()->id)
            ->whereIn('code', $validator->getOrderCodes())->get();
        $updatedOrderPackings = [];
        /** @var Order $order */
        foreach ($orders as $order) {
            $updatedOrderPackings = Service::orderPacking()->updateOrderPackings($order);
        }

        return $this->response()->success(['order_packings' => $updatedOrderPackings]);
    }

    /**
     * Chuyển trạng thái đơn không cần tuân theo workflow
     *
     * @return JsonResponse
     * @throws WorkflowException
     */
    public function changeStatus()
    {
        $input     = $this->request()->only(['tenant_code', 'order_codes', 'next_status']);
        $validator = (new ChangingStatusManualOrderValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $orders  = Order::query()->where('tenant_id', $validator->getTenant()->id)
            ->whereIn('code', $validator->getOrderCodes())->get();
        $changed = $changedWithoutWorkflow = [];

        /** @var Order $order */
        foreach ($orders as $order) {
            $orderPacking = $order->orderPacking;
            if ($orderPacking && in_array($input['next_status'], [Order::STATUS_WAITING_PROCESSING, Order::STATUS_WAITING_PICKING, Order::STATUS_WAITING_PACKING])
                && $orderPacking->status != OrderPacking::STATUS_PACKED) {
                if ($orderPacking->canChangeStatus($input['next_status'])) {
                    $orderPacking->changeStatus($input['next_status'], $this->user);
                    $changed[] = $order->code;
                } else {
                    Service::order()->changeStatusWithoutWorkflow($order, $input['next_status'], $this->user);
                    Service::orderPacking()->changeStatusWithoutWorkflow($orderPacking, $input['next_status'], $this->user);
                    $changedWithoutWorkflow[] = $order->code;
                }
            } else {
                if ($order->canChangeStatus($input['next_status'])) {
                    $order->changeStatus($input['next_status'], $this->user);
                    $changed[] = $order->code;
                } else {
                    Service::order()->changeStatusWithoutWorkflow($order, $input['next_status'], $this->user);
                    $changedWithoutWorkflow[] = $order->code;
                }
            }
        }

        return $this->response()->success([
            'changed' => $changed,
            'changed_without_workflow' => $changedWithoutWorkflow
        ]);
    }


    /**
     * Tạo document cho những đơn shopee
     *
     * @return JsonResponse
     */
    public function createShopeeDocument()
    {
        $input     = $this->request()->only(['tenant_code', 'order_codes']);
        $validator = (new CreateShopeeDocumentValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $orders = Order::query()->where('tenant_id', $validator->getTenant()->id)
            ->whereIn('code', $validator->getOrderCodes())->get();
        /** @var Order $order */
        foreach ($orders as $order) {
            if ($order->marketplace_code != Marketplace::CODE_SHOPEE) {
                print_r('order ' . $order->code . ' invalid');
                continue;
            }
            dispatch(new ShopeeCreateShippingDocumentJob($order->id));
        }

        return $this->response()->success();
    }

    /**
     * Tạo lại MVD cho đơn hàng chưa có thông tin bản ghi MVD
     *
     * @param Order $order
     * @return void
     */
    public function makeFreightBillCode()
    {
        $input   = $this->request()->only(['order_id']);
        $orderId = data_get($input, 'order_id', 0);
        Artisan::call("running_man makeFreightBillCode --order_id={$orderId}");
    }

    /**
     * Cập nhật vận đơn cho đơn
     * Có thể cập nhật ở mọi trạng thái đơn, logic còn lại giống việc cập nhật trên admin
     */
    public function importFreightBill(): JsonResponse
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $errors = Service::order()->importFreightBillManual($input['file'], $this->user);

        return $this->response()->success(compact('errors'));
    }

    /**
     * Cập nhật thông tin tài chính cho đơn
     *
     * @return JsonResponse
     */
    public function updatePaymentData()
    {
        $dataRequest = $this->request()->all();

        $dataReturn = ['message' => 'Import success'];

        $validator = Validator::make($dataRequest, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $updatePaymentData = (new UpdatePaymentData($dataRequest, $this->user))->handle();
        if ($updatePaymentData) {
            $dataReturn = $updatePaymentData;
        }

        return $this->response()->success($dataReturn);

    }
}
