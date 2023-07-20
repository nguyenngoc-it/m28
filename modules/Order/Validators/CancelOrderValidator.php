<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\User\Models\User;

class CancelOrderValidator extends Validator
{
    /**
     * @var Order|null
     */
    protected $order = null;

    /**
     * @var Order|null
     */
    protected $user = null;

    public function __construct(Order $order, User $user, $input = [])
    {
        $this->order = $order;
        $this->user = $user;
        parent::__construct($input);
    }

    public function rules()
    {
        return [
            'cancel_reason' => 'required|in:'.implode(',', Order::$cancelReasons),
        ];
    }

    protected function customValidate()
    {
        if (!Service::order()->canCancel($this->order, $this->user)) {
            return $this->errors()->add('status', static::ERROR_INVALID);
        }

        $cancelReason = trim($this->input['cancel_reason']);
        if($cancelReason == Order::CANCEL_REASON_OTHER && empty($this->input['cancel_note'])) {
            return $this->errors()->add('cancel_note', static::ERROR_REQUIRED);
        }
    }

}
