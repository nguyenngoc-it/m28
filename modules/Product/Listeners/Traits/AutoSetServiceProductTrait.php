<?php

namespace Modules\Product\Listeners\Traits;

use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductServicePrice;
use Modules\Service;
use Modules\User\Models\User;

trait AutoSetServiceProductTrait
{
    /**
     * @param Product $product
     * @param User $user
     * @param bool $autoPrice
     */
    protected function autoSetService(Product $product, User $user, $autoPrice = true)
    {
        /**
         * Nếu có dịch vụ bắt buộc chọn thì set cho sp
         */
        if ($autoPrice && $product->merchant) {
            $requiredServices = Service\Models\Service::query()->where([
                'tenant_id' => $product->tenant_id,
                'is_required' => true,
                'country_id' => $product->merchant->location_id,
            ])->get();
            foreach ($requiredServices as $requiredService) {
                Service::service()->setRequiredForProducts($requiredService, $product->creator, (new Collection([$product])));
            }
        }

        /**
         * Nếu sp thoả mãn các dịch vụ tự động chọn thì gán luôn
         */
        if ($autoPrice && $product->skus->count() == 1) {
            /** @var Service\Models\Service $service */
            foreach ($product->services as $service) {
                $autoServicePrice = Service::product()->autoGetSkuServicePrice($product->skus->first(), $service, $user);
                if ($autoServicePrice) {
                    $product->productServicePrices()->where('service_id', $service->id)->delete();
                    ProductServicePrice::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'tenant_id' => $product->tenant_id,
                            'service_price_id' => $autoServicePrice->id,

                        ],
                        [
                            'service_id' => $service->id,
                        ]
                    );
                }
            }
        }
    }
}
