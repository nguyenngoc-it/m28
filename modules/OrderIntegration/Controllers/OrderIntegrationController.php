<?php

namespace Modules\OrderIntegration\Controllers;

use App\Base\Controller;
use Gobiz\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Modules\Order\Commands\ChangeShippingPartner;
use Modules\Order\Commands\UpdateOrder;
use Modules\Order\Commands\UpdateOrderSKU;
use Modules\Order\Models\Order;
use Modules\Order\Transformers\OrderDetailTransformer;
use Modules\Order\Validators\ChangeShippingPartnerValidator;
use Modules\Order\Validators\UpdateOrderSKUValidator;
use Modules\Order\Validators\UpdateOrderValidator;
use Modules\OrderIntegration\Commands\ProcessCreatingOrder;
use Modules\OrderIntegration\Validators\ChangeShippingPartnerInternalValidator;
use Modules\OrderIntegration\Validators\UpdateOrderInternalValidator;

class OrderIntegrationController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function create()
    {
        try {
            $order = (new ProcessCreatingOrder($this->request()->toArray(), $this->getAuthUser()))->dispatch();
            return $this->response()->success(compact('order'));
        } catch (ValidationException $exception) {
            return $this->response()->error($exception->getValidator());
        }
    }

    /**
     * @param $code
     * @return JsonResponse
     */
    public function updateOrderSKU($code)
    {
        $creator           = $this->getAuthUser();
        $input             = $this->request()->all();
        $input['code']     = trim($code);
        $validatorInternal = (new UpdateOrderInternalValidator($input));
        if ($validatorInternal->fails()) {
            return $this->response()->error($validatorInternal);
        }

        $tenant = $validatorInternal->getTenant();
        if (empty($creator->tenant_id)) {
            $creator->tenant_id = $tenant->id;
        }
        $order = $validatorInternal->getOrder();

        $validator = (new UpdateOrderSKUValidator($order, $input, $creator));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $order = (new UpdateOrderSKU($order, $creator, $validator->getOrderSkus()))->handle();

        return $this->response()->success(compact('order'));
    }


    /**
     * @param $code
     * @return JsonResponse
     */
    public function update($code)
    {
        $creator           = $this->getAuthUser();
        $input             = $this->request()->only(array_merge(Order::$updateOrderParams, ['tenant', 'merchant']));
        $input['code']     = trim($code);
        $validatorInternal = (new UpdateOrderInternalValidator($input));
        if ($validatorInternal->fails()) {
            return $this->response()->error($validatorInternal);
        }

        $tenant = $validatorInternal->getTenant();
        if (empty($creator->tenant_id)) {
            $creator->tenant_id = $tenant->id;
        }
        $order = $validatorInternal->getOrder();

        $validator = (new UpdateOrderValidator($order, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $order = (new UpdateOrder($order, $input, $creator))->handle();

        return $this->response()->success(compact('order'));
    }

    /**
     * @param $code
     * @return JsonResponse
     */
    public function detail($code)
    {
        $input             = $this->request()->only(['tenant', 'merchant']);
        $input['code']     = trim($code);
        $validatorInternal = (new UpdateOrderInternalValidator($input));
        if ($validatorInternal->fails()) {
            return $this->response()->error($validatorInternal);
        }

        $data = (new OrderDetailTransformer($this->getAuthUser()))->transform($validatorInternal->getOrder());
        return $this->response()->success($data);
    }

    /**
     * Cập nhật đơn vị VC
     * @param $code
     * @return JsonResponse
     */
    public function shippingPartner($code)
    {
        $creator           = $this->getAuthUser();
        $input             = $this->request()->toArray();
        $input['code']     = trim($code);
        $validatorInternal = new ChangeShippingPartnerInternalValidator($input);
        if ($validatorInternal->fails()) {
            return $this->response()->error($validatorInternal);
        }

        $tenant = $validatorInternal->getTenant();
        if (empty($creator->tenant_id)) {
            $creator->tenant_id = $tenant->id;
        }

        $shippingPartner = $validatorInternal->getShippingPartner();
        $order           = $validatorInternal->getOrder();
        $validator       = new ChangeShippingPartnerValidator($order, $creator, ['shipping_partner_id' => $shippingPartner->id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $order = (new ChangeShippingPartner($order, $shippingPartner, $creator))->handle();
        return $this->response()->success(compact('order'));
    }
}
