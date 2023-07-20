<?php

namespace Modules\Transaction\Models;

use App\Base\MongoModel;
use Gobiz\Support\Traits\CachedPropertiesTrait;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Modules\Transaction\Jobs\ProcessTransactionJob;
use Modules\User\Models\User;
use MongoDB\BSON\UTCDateTime;

/**
 * Class Transaction
 *
 * @property string _id
 * @property int tenant_id
 * @property string action
 * @property string account_type
 * @property string account_id
 * @property int creator_id
 * @property array request
 * @property array response
 * @property string status
 * @property array logs
 * @property Tenant tenant
 * @property User creator
 */
class Transaction extends MongoModel
{
    use CachedPropertiesTrait;

    protected $collection = 'transactions';

    const ACTION_DEPOSIT  = 'DEPOSIT'; //nạp tièn từ ngoài hệ thống
    const ACTION_WITHDRAW = 'WITHDRAW'; //rút tièn từ ngoài hệ thống
    const ACTION_REFUND   = 'REFUND'; // hoàn tiền + ví seller
    const ACTION_COLLECT  = 'COLLECT'; //chi -  tiền ví seller
    const ACTION_GENESIS  = 'GENESIS'; //

    const TYPE_IMPORT_SERVICE              = 'IMPORT_SERVICE'; // Phí nhập hàng
    const TYPE_EXPORT_SERVICE              = 'EXPORT_SERVICE'; // Phí đóng hàng
    const TYPE_SHIPPING                    = 'SHIPPING'; // Phí giao hàng
    const TYPE_COD                         = 'COD'; // Thu hộ COD
    const TYPE_EXTENT                      = 'EXTENT'; // Phí mở rộng
    const TYPE_DEPOSIT                     = 'DEPOSIT'; //nạp tièn từ ngoài hệ thống
    const TYPE_WITHDRAW                    = 'WITHDRAW'; //rút tièn từ ngoài hệ thống
    const TYPE_STORAGE_FEE                 = 'STORAGE_FEE'; // Phí lưu kho
    const TYPE_IMPORT_RETURN_GOODS_SERVICE = 'IMPORT_RETURN_GOODS_SERVICE'; // Phí nhập hàng hoàn
    const TYPE_COST_OF_GOODS               = 'COST_OF_GOODS'; // Giá vốn
    const TYPE_SUPPLIER_PAYMENT              = 'SUPPLIER_PAYMENT'; // Thanh toan cho supplier


    const ACCOUNT_TYPE_MERCHANT         = 'MERCHANT';
    const ACCOUNT_TYPE_SHIPPING_PARTNER = 'SHIPPING_PARTNER';
    const ACCOUNT_TYPE_SUPPLIER_INVENTORY = 'SUPPLIER_INVENTORY';
    const ACCOUNT_TYPE_SUPPLIER_SOLD = 'SUPPLIER_SOLD';

    const STATUS_PENDING    = 'PENDING';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_SUCCESS    = 'SUCCESS';
    const STATUS_FAILED     = 'FAILED';

    /**
     * @return Tenant
     */
    public function getTenantAttribute()
    {
        return $this->getCachedProperty('tenant', function () {
            return Tenant::find($this->getAttribute('tenant_id'));
        });
    }

    /**
     * @return User
     */
    public function getCreatorAttribute()
    {
        return $this->getCachedProperty('creator', function () {
            return User::find($this->getAttribute('creator_id'));
        });
    }

    /**
     * @param string $message
     * @return bool
     */
    public function log($message)
    {
        return $this->push('logs', [
            'time' => new UTCDateTime,
            'message' => $message,
        ]);
    }

    /**
     * @return Transaction
     */
    public function process()
    {
        return Service::transaction()->process($this);
    }

    /**
     * Process transaction in queue
     */
    public function pushToQueue()
    {
        dispatch(new ProcessTransactionJob($this->getKey()));
    }
}
