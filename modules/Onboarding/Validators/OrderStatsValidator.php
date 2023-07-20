<?php

namespace Modules\Onboarding\Validators;

use App\Base\Validator;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class OrderStatsValidator extends Validator
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'created_at' => 'required',
        ];
    }

    /**
     * Custom validate
     */
    protected function customValidate()
    {
        $time = (array)$this->input['created_at'];
        $from = Arr::get($time, 'from');
        $to   = Arr::get($time, 'to');
        if(empty($from) || empty($to)) {
            $this->errors()->add('created_at', self::ERROR_INVALID);
            return;
        }

        $from = new Carbon($from);
        $to   = new Carbon($to);

        if($from > $to) {
            $this->errors()->add('created_at', self::ERROR_INVALID);
            return;
        }

        if($from->addMonth(1) <= $to) {
            // Không được quá 1 tháng
            $this->errors()->add('created_at', self::ERROR_INVALID);
            return;
        }
    }
}
