<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Gobiz\Redis\RedisService;
use Illuminate\Redis\Connections\Connection;

class ImportingBashOrderValidator extends Validator
{
    /** @var array $validCachedOrder */
    protected $validCachedOrders = [];
    /** @var array $warningCachedOrder */
    protected $warningCachedOrders = [];
    /** @var array $bothCachedOrders */
    protected $bothCachedOrders = [];
    /** @var array $cachedOrders */
    protected $cachedOrders = [];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'bash' => 'required|in:warning,valid,both',
        ];
    }

    /**
     * @return array
     */
    public function getCachedOrders(): array
    {
        return $this->cachedOrders;
    }

    /**
     * @return void
     */
    protected function customValidate()
    {
        /** @var Connection $redis */
        $redis = RedisService::redis()->connection();
        $bash  = $this->input('bash');
        switch ($bash) {
            case 'valid':
                return $this->bashValid($redis);
            case 'warning':
                return $this->bashWaring($redis);
            case 'both':
                return $this->bashBoth($redis);
        }
    }

    protected function bashValid(Connection $redis, $addError = true)
    {
        $key                     = $this->user->tenant->code . '_' . $this->user->username . '_bash_valid_orders';
        $this->validCachedOrders = json_decode($redis->get($key), true);
        $this->cachedOrders      = $this->validCachedOrders;
        if (empty($this->validCachedOrders) && $addError) {
            $this->errors()->add('valid', 'not_found');
            return;
        }
    }

    protected function bashWaring(Connection $redis, $addError = true)
    {
        $key                       = $this->user->tenant->code . '_' . $this->user->username . '_bash_warning_orders';
        $this->warningCachedOrders = json_decode($redis->get($key), true);
        $this->cachedOrders        = $this->warningCachedOrders;
        if (empty($this->warningCachedOrders) && $addError) {
            $this->errors()->add('warning', 'not_found');
            return;
        }
    }

    protected function bashBoth(Connection $redis)
    {
        $this->bashValid($redis, false);
        $this->bashWaring($redis, false);
        $this->bothCachedOrders = array_merge((array)$this->validCachedOrders, (array)$this->warningCachedOrders);
        $this->cachedOrders     = $this->bothCachedOrders;
        if (empty($this->bothCachedOrders)) {
            $this->errors()->add('both', 'not_found');
            return;
        }
    }
}
