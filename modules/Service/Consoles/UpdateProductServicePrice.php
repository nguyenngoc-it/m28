<?php

namespace Modules\Service\Consoles;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductServicePrice;
use Modules\Service\Models\ServicePrice;
use Modules\Tenant\Models\Tenant;

class UpdateProductServicePrice extends Command
{
    protected $signature = 'service:UpdateProductServicePrice {--tenantId=} {--type=} {--limit=200}';

    public function handle()
    {
        $limit    = $this->option('limit');
        $type     = $this->option('type');
        $tenantId = $this->option('tenantId');
        $tenant   = Tenant::query()->where('id', $tenantId)->first();
        if ($tenant) {
            $shopeeMissingServiceProducts = $this->getShopeeMissingServiceProducts($tenant->id, $type, $limit);
            $requiredImportServices       = $this->getRequiredImportService($tenant->id);
            $this->addServiceToMissingProducts($requiredImportServices, $shopeeMissingServiceProducts);
        } else
            $this->error("không tồn tại tenantId= $tenantId");
    }

    /** Lấy danh sách sản phẩm shopee chưa có đơn giá dịch vụ
     * @return Collection
     */
    public function getShopeeMissingServiceProducts(int $tenantID, $type, $limit)
    {
        $shopeeMissingServiceProducts = Product::select('products.*')
            ->leftJoin('product_service_prices', 'product_service_prices.product_id', '=', 'products.id')
            ->whereNull('product_service_prices.product_id')
            ->where('products.source', $type)
            ->where('products.tenant_id', $tenantID)
            ->limit($limit)
            ->get();
        return $shopeeMissingServiceProducts;
    }

    /** Lấy đơn giá 1 dịch vụ nhập bắt buộc của 1 tenant
     * @param int $tenantId
     * @return Collection
     */
    protected function getRequiredImportService(int $tenantId)
    {
        $requiredImportServices = ServicePrice::select('services.id as service_id', 'service_prices.*')
            ->join('services', 'service_prices.service_code', '=', 'services.code')
            ->where('services.type', '=', 'IMPORT')
            ->where('services.is_required', '=', 1)
            ->where('service_prices.is_default', '=', 1)
            ->where('services.tenant_id', '=', $tenantId)
            ->get();
        return $requiredImportServices;
    }

    /** Gán dịch vụ bắt buộc nhập cho sản phẩm thiếu
     * @param $requiredImportServices
     * @param $shopeeMissingServiceProducts
     * @return void
     */
    private function addServiceToMissingProducts($requiredImportServices, $shopeeMissingServiceProducts)
    {
        $productServicePrice = new ProductServicePrice();
        foreach ($shopeeMissingServiceProducts as $shopeeMissingServiceProduct) {
            foreach ($requiredImportServices as $requiredImportService) {
                $productServicePrice->create([
                    'product_id' => $shopeeMissingServiceProduct->id,
                    'tenant_id' => $requiredImportService->tenant_id,
                    'service_price_id' => $requiredImportService->id,
                    'service_id' => $requiredImportService->service_id,
                ]);
            }
        }
    }
}
