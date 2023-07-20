<?php

namespace Modules\Supplier\Models;

use App\Base\Model;
use Gobiz\Support\Traits\CachedPropertiesTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SupplierTransaction\Models\SupplierTransaction;
use Modules\Tenant\Models\Tenant;
use Modules\Transaction\Commands\SupplierTransCreating;
use Modules\Transaction\Models\Transaction;
use Modules\Transaction\Services\SupplierTransObjInterface;
use Modules\Transaction\Services\TransactionAccount;
use Modules\Transaction\Services\Wallet;

/**
 * Class Supplier
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $parent_id
 * @property string $code
 * @property string $name
 * @property string $note
 * @property int $position
 * @property float total_purchased_amount
 * @property float total_sold_amount
 * @property float total_paid_amount
 * @property float total_returned_amount
 * @property Supplier|null $parent
 *
 * @property Tenant|null $tenant
 */
class Supplier extends Model
{
    use CachedPropertiesTrait;

    protected $table = 'suppliers';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'note', 'address', 'contact'
    ];

    const WALLET_INVENTORY = 'INVENTORY'; // Ví công nợ tồn kho
    const WALLET_SOLD = 'SOLD'; // Ví công nợ đã bán

    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Supplier::class, 'parent_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(SupplierTransaction::class, 'supplier_id', 'id');
    }

    /** filter theo tenantId
     * @param $query
     * @param $tenantId
     * @return mixed
     */
    public function scopeTenant($query, $tenantId)
    {
        if ($tenantId) {
            return $query->where('suppliers.tenant_id', $tenantId);
        } else
            return $query;
    }

    public function scopeCode($query, $code)
    {
        if ($code) {
            return $query->where('suppliers.code', $code);
        } else
            return $query;
    }

    /**
     * @return Wallet
     */
    public function inventoryWallet()
    {
        return $this->getCachedProperty('inventoryWallet', function () {
            return $this->makeWallet(static::WALLET_INVENTORY, Transaction::ACCOUNT_TYPE_SUPPLIER_INVENTORY);
        });
    }

    /**
     * @return Wallet
     */
    public function soldWallet()
    {
        return $this->getCachedProperty('soldWallet', function () {
            return $this->makeWallet(static::WALLET_SOLD, Transaction::ACCOUNT_TYPE_SUPPLIER_SOLD);
        });
    }

    protected function makeWallet($walletType, $transactionAccountType)
    {
        return new Wallet(
            $this->tenant->m4Supplier(),
            $this->makeWalletCode($walletType),
            $this->makeTransactionAccount($transactionAccountType)
        );
    }

    /**
     * @param $walletType
     * @return string
     */
    public function makeWalletCode($walletType)
    {
        return $this->getAttribute('code') . '-' . $walletType;
    }

    /**
     * @param string $transType
     * @param float $amount
     * @param SupplierTransObjInterface $supplierTransObj
     * @return SupplierTransCreating
     */
    public function buildSupplierTransaction(string $transType, float $amount, SupplierTransObjInterface $supplierTransObj)
    {
        return new SupplierTransCreating($this, $transType, $amount, $supplierTransObj);
    }

    /**
     * @param $transactionAccountType
     * @return TransactionAccount
     */
    protected function makeTransactionAccount($transactionAccountType)
    {
        return new TransactionAccount(
            $this->getAttribute('tenant_id'),
            $transactionAccountType,
            $this->getKey(),
        );
    }
}
