<?php

namespace Modules\OrderIntegration\Commands;

use App\Base\CommandBus;
use Exception;
use Gobiz\Transformer\TransformerService;
use Gobiz\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Modules\InvalidOrder\Models\InvalidOrder;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Commands\CreateOrder;
use Modules\Order\Models\Order;
use Modules\Order\Validators\CreateOrderValidator;
use Modules\OrderIntegration\Validators\CreateOrderInternalValidator;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class ProcessCreatingOrder extends CommandBus
{
    /**
     * @var array
     */
    public $input;

    /**
     * @var User
     */
    public $creator;

    /**
     * ProcessCreateOrder constructor
     *
     * @param array $input
     * @param User $creator
     */
    public function __construct(array $input, User $creator)
    {
        $this->input = $input;
        $this->creator = $creator;
    }

    /**
     * @return Order
     * @throws ValidationException
     * @throws Exception
     */
    public function handle()
    {
        $input = $this->input;
        $validatorInternal = new CreateOrderInternalValidator($input);

        if ($validatorInternal->fails()) {
            if ($tenant = $validatorInternal->getTenant()) {
                $this->makeInvalidOrder($tenant, $validatorInternal);
            }

            throw new ValidationException($validatorInternal);
        }

        $tenant = $validatorInternal->getTenant();
        $merchant  = $validatorInternal->getMerchant();
        $receiverProvince = $validatorInternal->getReceiverProvince();
        $receiverDistrict = $validatorInternal->getReceiverDistrict();
        $receiverWard = $validatorInternal->getReceiverWard();
        $shippingPartner = $validatorInternal->getShippingPartner();

        $input['merchant_id'] = $merchant->id;
        $input['receiver_province_id'] = isset($receiverProvince->id) ? $receiverProvince->id : 0;
        $input['receiver_district_id'] = isset($receiverDistrict->id) ? $receiverDistrict->id : 0;
        $input['receiver_ward_id'] = isset($receiverWard->id) ? $receiverWard->id : 0;
        $input['shipping_partner_id'] = isset($shippingPartner->id) ? $shippingPartner->id : 0;
        $input['marketplace_code'] = $this->getMarketplaceCode();

        $validator = (new CreateOrderValidator($tenant, $input));

        if ($validator->fails()) {
            $this->makeInvalidOrder($tenant, $validator);
            throw new ValidationException($validator);
        }

        $order = (new CreateOrder(array_merge($input, [
            'creator' => $this->creator,
            'merchant' => $validator->getMerchant(),
            'orderSkus' => $validator->getOrderSkus(),
            'receiverCountry' => $validator->getReceiverCountry(),
            'receiverProvince' => $validator->getReceiverProvince(),
            'receiverDistrict' => $validator->getReceiverDistrict(),
            'receiverWard' => $validator->getReceiverWard(),
            'orderAmount' => $validator->getOrderAmount(),
            'totalAmount' => $validator->getTotalAmount(),
            'extraServices' => $validator->getExtraServices(),
            'shippingPartner' => $validator->getShippingPartner(),
        ])))->handle();

        Service::invalidOrder()->remove(InvalidOrder::SOURCE_INTERNAL_API, $order);

        return $order;
    }

    /**
     * @return string|null
     */
    protected function getMarketplaceCode()
    {
        return $this->creator->username === User::USERNAME_FOBIZ ? Marketplace::CODE_FOBIZ : null;
    }

    /**
     * @param Tenant $tenant
     * @param Validator $validator
     * @return InvalidOrder|object|null
     */
    protected function makeInvalidOrder(Tenant $tenant, Validator $validator)
    {
        if (!$code = Arr::get($this->input, 'code')) {
            return null;
        }

        $errors = TransformerService::transform($validator);

        return InvalidOrder::query()->updateOrCreate([
            'tenant_id' => $tenant->id,
            'source' => InvalidOrder::SOURCE_INTERNAL_API,
            'code' => $code,
        ], [
            'payload' => $this->input,
            'error_code' => $this->getErrorCode($errors),
            'errors' => $errors,
            'creator_id' => $this->creator->id,
        ]);
    }

    /**
     * @param array $errors
     * @return string
     */
    protected function getErrorCode(array $errors)
    {
        if (isset($errors['merchant_id']) || isset($errors['merchant'])) {
            return InvalidOrder::ERROR_MERCHANT_UNMAPPED;
        }

        if (isset($errors['sku_errors'])) {
            return InvalidOrder::ERROR_SKU_UNMAPPED;
        }

        return InvalidOrder::ERROR_TECHNICAL;
    }
}
