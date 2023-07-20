<?php

namespace Modules\Document\Commands;

use Carbon\Carbon;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuInventory;
use Modules\Document\Services\DocumentEvent;
use Modules\Service;
use Modules\User\Models\User;

class BalanceSku
{
    /** @var Document */
    protected $documentSkuInventory;
    /** @var User $user */
    protected $user;

    /**
     * BalanceSku constructor.
     * @param Document $documentSkuInventory
     * @param User $user
     */
    public function __construct(Document $documentSkuInventory, User $user)
    {
        $this->documentSkuInventory = $documentSkuInventory;
        $this->user                 = $user;
    }

    /**
     * @return Document
     */
    public function handle()
    {
        /** @var DocumentSkuInventory $skuInventory */
        foreach ($this->documentSkuInventory->documentSkuInventories as $skuInventory) {
            $quantity = abs($skuInventory->quantity_balanced);

            if ($skuInventory->quantity_balanced < 0) {
                $skuInventory->stock->export($quantity, $this->user, $this->documentSkuInventory)
                    ->with(['explain' => $skuInventory->explain])->run();
            } else {
                $skuInventory->stock->import($quantity, $this->user, $this->documentSkuInventory)
                    ->with(['explain' => $skuInventory->explain])->run();
            }

            $quantityInStock                 = $skuInventory->stock->refresh()->real_quantity;
            $skuInventory->quantity_balanced = 0;
            $skuInventory->quantity_in_stock = $quantityInStock;
            $skuInventory->save();
        }

        $documentInfo                     = $this->documentSkuInventory->info;
        $documentInfo['balanced_at']      = Carbon::now()->format('Y-m-d H:i:s');
        $this->documentSkuInventory->info = $documentInfo;
        $this->documentSkuInventory->save();
        $this->documentSkuInventory->logActivity(DocumentEvent::BALANCE_INVENTORY, $this->user);
        Service::documentSkuInventory()->completeDocument($this->documentSkuInventory, $this->user);
        return $this->documentSkuInventory->refresh();
    }
}
