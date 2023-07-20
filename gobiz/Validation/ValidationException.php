<?php

namespace Gobiz\Validation;

use Illuminate\Contracts\Validation\Validator as ValidatorInterface;
use Throwable;

class ValidationException extends \Exception
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * ValidationException constructor.
     * @param ValidatorInterface $validator
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(ValidatorInterface $validator, $message = "", $code = 0, Throwable $previous = null)
    {
        $message = $message ?: $this->makeMessageFromValidator($validator);
        $this->validator = $validator;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param ValidatorInterface $validator
     * @return string
     */
    protected function makeMessageFromValidator(ValidatorInterface $validator)
    {
        $class = get_class($validator);

        try {
            return $class.': '.$validator->errors()->toJson();
        } catch (Throwable $exception) {
            return $class;
        }
    }

    /**
     * @return ValidatorInterface
     */
    public function getValidator()
    {
        return $this->validator;
    }
}
