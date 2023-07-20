<?php
namespace Modules\Service\Commands;

use Modules\Product\Models\Product;
use Modules\Service\Jobs\UpdateServicePriceProductJob;
use Modules\Service\Models\Service;
use Modules\User\Models\User;

class UpdateServicePriceAllMerchantsCommand
{

    /**
     * @var array
     */
    protected $input;

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * ChangeStatusServiceCommand constructor.
     * @param array $input
     * @param User $creator
     */
    public function __construct($input, User $creator)
    {
        $this->input   = $input;
        $this->creator = $creator;
    }

    /**
     * @return void
     */
    public function handle()
    {
        // Lấy danh sách dịch vụ theo country và type
        $countryId = data_get($this->input, 'country_id', 0);

        // Lấy tất cả sản phẩm của các seller tại thị trường này
        $products = Product::whereHas('merchants', function ($query) use ($countryId) {
            return $query->where('merchants.location_id', $countryId);
        })->get();

        foreach ($products->chunk(200) as $key => $productChunk) {
            dispatch(new UpdateServicePriceProductJob($productChunk, $this->creator));
        }

        return 'aloha hi hi';
    }
}
