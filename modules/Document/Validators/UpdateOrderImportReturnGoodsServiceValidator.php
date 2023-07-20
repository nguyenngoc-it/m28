<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Order\Models\Order;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Modules\User\Models\User;

class UpdateOrderImportReturnGoodsServiceValidator extends Validator
{
    /**
     * @var Collection|null
     */
    protected $orders;

    /**
     * @var Collection|null
     */
    protected $servicePrices;

    /**
     * @var User|null
     */
    protected $user = null;

    public function __construct(User $user, $input = [])
    {
        $this->user = $user;
        parent::__construct($input);
    }

    public function rules(): array
    {
        return [
            'order_ids' => 'required|array',
            'service_price_ids' => 'array',
        ];
    }

    protected function customValidate()
    {
        $this->orders = $this->user->tenant->orders()->whereIn('id', $this->input['order_ids'])->get();
        if (!$this->orders->count()) {
            $this->errors()->add('order_ids', static::ERROR_INVALID);
            return;
        }

        if(!empty($this->input['service_price_ids'])) {
            $this->servicePrices = $this->user->tenant->servicePrices()->whereIn('id', $this->input['service_price_ids'])->get();
            if (!$this->servicePrices->count()) {
                $this->errors()->add('service_price_ids', static::ERROR_INVALID);
                return;
            }
        }

        /**
         * Nếu đơn hàng của seller có sử dụng gói dịch vụ thì bắt buộc phải đủ các dịch vụ xuất của nhóm dịch vụ
         */
        /** @var Order $order */
        $order = $this->orders->first();
        if ($order->merchant && $order->merchant->servicePack) {
            if (empty($this->servicePrices) || $this->servicePrices->count() == 0) {
                $this->errors()->add('service_price_ids', 'missing_service');
                return;
            }

            $packServices          = $order->merchant->servicePack->servicePackPrices()
                ->join('services', 'service_pack_prices.service_id', '=', 'services.id')
                ->where('services.type', Service::SERVICE_TYPE_IMPORTING_RETURN_GOODS)
                ->select('services.id')->get();
            $packServiceIds        = $packServices->pluck('id')->all();
            $requiredServiceIds    = $packServices
                ->where('services.is_required', true)
                ->pluck('id')->all();
            $inputServiceIds       = $this->servicePrices->map(function (ServicePrice $servicePrice) {
                return $servicePrice->service->id;
            })->unique()->all();
            $inputExportServiceIds = $this->servicePrices->map(function (ServicePrice $servicePrice) {
                return $servicePrice->service->type == Service::SERVICE_TYPE_IMPORTING_RETURN_GOODS ? $servicePrice->service->id : null;
            })->unique()->filter()->values()->all();

            /**
             * Thiếu dịch vụ bắt buộc
             */
            if (array_diff($requiredServiceIds, $inputServiceIds)) {
                $this->errors()->add('service_price_ids', 'missing_service_required');
                return;
            }

            /**
             * Truyền lên dịch vụ không có trong gói
             */
            if (array_diff($inputServiceIds, $packServiceIds)) {
                $this->errors()->add('service_price_ids', 'service_invalid');
                return;
            }

            /**
             * Truyền lên ko đủ dịch vụ xuất
             */
            if (empty($inputExportServiceIds)) {
                $this->errors()->add('service_price_ids', 'missing_service');
                return;
            }
        }

    }

    /**
     * @return Collection|null
     */
    public function getOrders(): ?Collection
    {
        return $this->orders;
    }


    /**
     * @return Collection|null
     */
    public function getServicePrices(): ?Collection
    {
        return $this->servicePrices;
    }
}
