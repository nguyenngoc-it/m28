<?php

namespace Modules\ActiveCode\Validators;

use App\Base\Validator;
use InvalidArgumentException;
use Modules\ActiveCode\Models\ActiveCode;
use Modules\Service\Models\ServiceCombo;

class ActiveCodeValidator extends Validator
{
    public $input;

    public function rules()
    {
        return [
            'service_combo_id' => 'required|int',
            'type' => 'required'
        ];
    }

    protected function customValidate()
    {
        $id   = data_get($this->input, 'service_combo_id');
        $type = data_get($this->input, 'type');

        if (!in_array($type, ActiveCode::TYPE_SERVICE)) {
            $this->errors()->add('type_service', static::ERROR_INVALID);
        }

        $serviceCombo = ServiceCombo::query()->where('id', $id)->first();
        if (!$serviceCombo){
            $this->errors()->add('service_combo', static::ERROR_EXISTS);
        }
    }

}
