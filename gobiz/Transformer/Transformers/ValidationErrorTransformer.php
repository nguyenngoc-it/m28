<?php

namespace Gobiz\Transformer\Transformers;

use Gobiz\Transformer\TransformerInterface;
use Illuminate\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ValidationErrorTransformer implements TransformerInterface
{
    /**
     * @var array
     */
    protected $ruleParams = [
        'After' => ['date'],
        'AfterOrEqual' => ['date'],
        'Before' => ['date'],
        'BeforeOrEqual' => ['date'],
        'Between' => ['min', 'max'],
        'DateFormat' => ['format'],
        'Different' => ['other'],
        'Digits' => ['digits'],
        'DigitsBetween' => ['min', 'max'],
        'InArray' => ['other'],
        'Max' => ['max'],
        'Mimes' => ['values'],
        'Mimetypes' => ['values'],
        'Min' => ['min'],
        'RequiredIf' => ['other', 'value'],
        'RequiredUnless' => ['other', 'values'],
        'RequiredWith' => ['values'],
        'RequiredWithAll' => ['values'],
        'RequiredWithout' => ['values'],
        'RequiredWithoutAll' => ['values'],
        'Same' => ['other'],
        'Size' => ['size'],
        'Gt' => ['value'],
        'Gte' => ['value'],
    ];

    /**
     * @param Validator $validator
     * @return array
     */
    public function transform($validator)
    {
        return $this->hasFailedRules($validator)
            ? $this->transformByFailedRules($validator)
            : $this->transformByErrors($validator);
    }

    /**
     * @param Validator $validator
     * @return bool
     */
    protected function hasFailedRules(Validator $validator)
    {
        return count($validator->failed()) > 0;
    }

    /**
     * @param Validator $validator
     * @return array
     */
    protected function transformByFailedRules(Validator $validator)
    {
        $res = [];

        foreach ($validator->failed() as $attribute => $rules) {
            $res[$attribute] = $this->transformAttribute($validator, $attribute, $rules);
        }

        return $res;
    }

    /**
     * @param Validator $validator
     * @return array
     */
    protected function transformByErrors(Validator $validator)
    {
        return array_map(function ($messages) {
            return $this->transformCustomErrorMessages($messages);
        }, $validator->errors()->toArray());
    }

    /**
     * @param array $messages
     * @return array
     */
    protected function transformCustomErrorMessages(array $messages)
    {
        $result = [];

        foreach ($messages as $error => $payload) {
            if (is_array($payload)) {
                $result = array_merge($result, $payload);
            } else {
                $result[$payload] = [];
            }
        }

        return $result;
    }

    /**
     * @param Validator $validator
     * @param string $attribute
     * @param array $rules
     * @return array
     */
    protected function transformAttribute(Validator $validator, $attribute, array $rules)
    {
        $result = [];
        foreach ($rules as $rule => $params) {
            $outputRule = $this->transformRule($rule);

            if ($this->isSizeRule($rule)) {
                $outputRule .= '.' . $this->getAttributeType($validator, $attribute);
            }

            $result[$outputRule] = $this->transformRuleParams($rule, $params);
        }

        return $result;
    }

    /**
     * @param string $rule
     * @return string
     */
    protected function transformRule($rule)
    {
        $parsed = explode('\\', $rule);
        $outputRule = Str::snake(Arr::last(explode('\\', $rule)));

        return count($parsed) > 2 ? 'custom.' . $outputRule : $outputRule;
    }

    /**
     * @param string $rule
     * @param array $values
     * @return array
     */
    protected function transformRuleParams($rule, array $values)
    {
        if (!$params = Arr::get($this->ruleParams, $rule)) {
            return [];
        }

        $values = array_pad($values, count($params), null);

        return array_combine($params, $values);
    }

    /**
     * @param string $rule
     * @return bool
     */
    protected function isSizeRule($rule)
    {
        return in_array($rule, ['Size', 'Between', 'Min', 'Max']);
    }

    /**
     * @param Validator $validator
     * @param  string $attribute
     * @return string
     */
    protected function getAttributeType(Validator $validator, $attribute)
    {
        if ($validator->hasRule($attribute, ['Numeric', 'Integer'])) {
            return 'numeric';
        }

        if ($validator->hasRule($attribute, ['Array'])) {
            return 'array';
        }

        if ($this->getAttributeValue($validator, $attribute) instanceof UploadedFile) {
            return 'file';
        }

        return 'string';
    }

    /**
     * @param Validator $validator
     * @param string $attribute
     * @return mixed
     */
    protected function getAttributeValue(Validator $validator, $attribute)
    {
        return Arr::get($validator->getData(), $attribute);
    }
}
