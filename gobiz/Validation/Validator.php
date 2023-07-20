<?php

namespace Gobiz\Validation;

use Illuminate\Contracts\Validation\Validator as ValidatorInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator as BaseValidator;

abstract class Validator implements ValidatorInterface
{
    /**
     * Input
     *
     * @var array
     */
    protected $input = [];

    /**
     * The validator implementation
     *
     * @var BaseValidator
     */
    protected $validator;

    /**
     * Validator constructor
     *
     * @param array $input
     */
    public function __construct(array $input = [])
    {
        $this->input = $input;

        $this->validator = $this->makeValidator($input);

        $this->validator->after(function () {
            if ($this->errors()->isEmpty()) {
                $this->customValidate();
            }
        });
    }

    /**
     * Custom validate
     */
    protected function customValidate()
    {
    }

    /**
     * Make validator instance
     *
     * @param array $input
     * @return BaseValidator
     */
    protected function makeValidator(array $input)
    {
        return validator($input, $this->rules(), $this->messages(), $this->labels());
    }

    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Get validation messages
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * Get validation labels
     *
     * @return array
     */
    public function labels()
    {
        return [];
    }

    /**
     * Get the label of a field
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function label($key, $default = null)
    {
        return Arr::get($this->labels(), $key, $default);
    }

    /**
     * Get validation input
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        return Arr::get($this->input, $key, $default);
    }

    /**
     * Get the messages for the instance.
     *
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function getMessageBag()
    {
        return $this->validator->getMessageBag();
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        return $this->validator->validate();
    }

    /**
     * @inheritDoc
     */
    public function validated()
    {
        return $this->validator->validated();
    }

    /**
     * Determine if the data fails the validation rules.
     *
     * @return bool
     */
    public function fails()
    {
        return $this->validator->fails();
    }

    /**
     * Get the failed validation rules.
     *
     * @return array
     */
    public function failed()
    {
        return $this->validator->failed();
    }

    /**
     * Add conditions to a given field based on a Closure.
     *
     * @param  string|array $attribute
     * @param  string|array $rules
     * @param  callable $callback
     * @return ValidatorInterface
     */
    public function sometimes($attribute, $rules, callable $callback)
    {
        return $this->validator->sometimes($attribute, $rules, $callback);
    }

    /**
     * After an after validation callback.
     *
     * @param  callable|string $callback
     * @return ValidatorInterface
     */
    public function after($callback)
    {
        return $this->validator->after($callback);
    }

    /**
     * Get all of the validation error messages.
     *
     * @return MessageBag
     */
    public function errors()
    {
        return $this->validator->errors();
    }

    /**
     * @return BaseValidator
     */
    public function getBaseValidator()
    {
        return $this->validator;
    }
}
