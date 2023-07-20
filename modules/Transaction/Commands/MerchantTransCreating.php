<?php

namespace Modules\Transaction\Commands;

use InvalidArgumentException;
use Modules\Merchant\Models\Merchant;
use Modules\Transaction\Models\MerchantTransaction;
use Modules\Transaction\Models\Transaction;
use Modules\Transaction\Services\MerchantTransObjInterface;

class MerchantTransCreating
{
    /**
     * @var Merchant
     */
    protected $merchant_id;
    /**
     * @var float
     */
    protected $amount;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $object_type;
    /**
     * @var string
     */
    protected $object_id;
    /**
     * @var array
     */
    protected $metadata;

    /**
     * @var string
     */
    protected $transId;

    public function __construct(Merchant $merchant, string $action, string $transType, float $amount, MerchantTransObjInterface $merchantTransObj)
    {
        switch ($action) {
            case MerchantTransaction::ACTION_COLLECT:
                if (!in_array($transType, [
                    Transaction::TYPE_IMPORT_RETURN_GOODS_SERVICE, Transaction::TYPE_IMPORT_SERVICE,
                    Transaction::TYPE_EXPORT_SERVICE, Transaction::TYPE_SHIPPING, Transaction::TYPE_EXTENT,
                    Transaction::TYPE_COD, Transaction::TYPE_COST_OF_GOODS
                ])) {
                    throw new InvalidArgumentException('Transaction type invalid!!!');
                }
                break;
            case MerchantTransaction::ACTION_REFUND:
                if ($transType != Transaction::TYPE_COD) {
                    throw new InvalidArgumentException('Transaction type invalid!!!');
                }
                break;
        }
        if (empty($amount)) {
            throw new InvalidArgumentException('Amount is empty!!!');
        }
        $this->merchant_id = $merchant->id;
        $this->amount      = $amount;
        $this->type        = $transType;
        $this->object_type = $merchantTransObj->getObjectType();
        $this->object_id   = $merchantTransObj->getObjectId();
    }

    /**
     * @return MerchantTransaction
     */
    public function create()
    {
        return MerchantTransaction::create([
            'merchant_id' => $this->merchant_id,
            'type' => $this->type,
            'object_type' => $this->object_type,
            'object_id' => $this->object_id,
            'amount' => $this->amount,
            'metadata' => $this->metadata,
            'trans_id' => $this->transId
        ]);
    }

    /**
     * @param string $transId
     * @return $this
     */
    public function withTransId(string $transId)
    {
        $this->transId = $transId;
        return $this;
    }

    /**
     * @param array $metadata
     * @return $this
     */
    public function withMeta(array $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }
}
