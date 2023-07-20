<?php

namespace App\Base;

use Gobiz\Validation\Validator as BaseValidator;
use Illuminate\Support\Facades\Auth;
use Modules\User\Models\User;

abstract class Validator extends BaseValidator
{
    const ERROR_EXISTS            = 'exists';
    const ERROR_EXISTS_OR_INVALID = 'exists_or_invalid';
    const ERROR_NOT_EXIST         = 'not_exist';
    const ERROR_EMPTY             = 'empty';
    const ERROR_REQUIRED          = 'required';
    const ERROR_GREATER           = 'greater';
    const ERROR_LESSER            = 'lesser';
    const ERROR_NOT_FOUND         = 'not_found';
    const ERROR_INVALID           = 'invalid';
    const ERROR_STATUS_INVALID    = 'status_invalid';
    const ERROR_ALREADY_EXIST     = 'already_exist';
    const ERROR_UNIQUE            = 'unique';
    const ERROR_NOT_NUMBER        = 'not_number';
    const ERROR_403               = 'unauthorized';
    const ERROR_DUPLICATED        = 'duplicated';

    /** @var User $user */
    protected $user;

    /**
     * Validator constructor.
     * @param array $input
     * @param User|null $user
     */
    public function __construct(array $input = [], User $user = null)
    {
        parent::__construct($input);
        if (empty($user)) {
            $this->user = Auth::user();
        } else {
            $this->user = $user;
        }
    }

}
