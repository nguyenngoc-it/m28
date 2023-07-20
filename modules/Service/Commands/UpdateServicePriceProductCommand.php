<?php
namespace Modules\Service\Commands;

use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Events\ProductUpdated;
use Modules\Service\Models\Service;
use Modules\User\Models\User;

class UpdateServicePriceProductCommand
{

    /**
     * @var Collection
     */
    protected $products;

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * ChangeStatusServiceCommand constructor.
     * @param array $input
     * @param User $creator
     */
    public function __construct(Collection $products, User $creator)
    {
        $this->products = $products;
        $this->creator  = $creator;
    }

    /**
     * @return void
     */
    public function handle()
    {
        foreach ($this->products as $product) {
            (new ProductUpdated($product->id, $this->creator->id, []))->queue();
        }
        return 'aloha hi hi';
    }
}
