<?php

namespace Modules\DeliveryNote\Commands;

use Illuminate\Support\Arr;
use Modules\DeliveryNote\Models\DeliveryNote;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;

class CreateDeliveryNote
{
    /**
     * @var array
     */
    protected $input = [];

    /**
     * @var User
     */
    protected $user;

    /**
     * CreateDeliveryNote constructor.
     * @param array $input
     * @param User $user
     */
    public function __construct(array $input, User $user)
    {
        $this->input = $input;
        $this->user = $user;
    }

    public function handle()
    {
        $skus = $this->input['skus'];
        $deliveryNote = DeliveryNote::create([
            'warehouse_id' => $this->input['warehouse_id'],
            'note' => $this->input['note'],
            'creator_id' => $this->user->id,
            'tenant_id' => $this->user->tenant_id
        ]);


        foreach ($skus as $sku) {
            $stock = Arr::pull($sku, 'stock');
            if ($stock instanceof Stock) {
                $stock->do(Stock::ACTION_EXPORT, $sku['quantity'], $this->user)
                    ->with($sku)
                    ->for($deliveryNote)
                    ->run();
            }
        }

        return $deliveryNote;
    }
}
