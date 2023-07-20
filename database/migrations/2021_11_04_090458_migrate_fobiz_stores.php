<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Store\Models\Store;
use Modules\Marketplace\Services\Marketplace;
use Modules\Product\Models\Sku;
use Modules\Store\Models\StoreSku;

class MigrateFobizStores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('marketplace_store_id')
                ->nullable()->comment('Định danh của store trên marketplace')->change();
        });

        Schema::table('store_skus', function (Blueprint $table) {
            $table->string('marketplace_store_id')
                ->nullable()->comment('Định danh của store trên marketplace')->change();
        });

        $store = Store::create([
            'tenant_id' => 1,
            'merchant_id' => 1,
            'marketplace_code' => Marketplace::CODE_FOBIZ,
            'marketplace_store_id' => null,
            'name' => 'Fobiz',
            'status' => Store::STATUS_ACTIVE
        ]);

        $skus = Sku::query()
            ->where('tenant_id', 1)
            ->where('fobiz_code', '!=', '')
            ->get();
        foreach ($skus as $sku) {
            StoreSku::create([
                'tenant_id' => $store->tenant_id,
                'store_id' => $store->id,
                'marketplace_code' => $store->marketplace_code,
                'marketplace_store_id' => $store->marketplace_store_id,
                'sku_id' => $sku->id,
                'code' => $sku->fobiz_code
            ]);
        }

        $fobizSkuMapping = [
            "sku11595836531110" => "EZ.04.033_440",
            "sku21614249836555" => "EZ.04.033_440",
            "sku01593165871929" => "EZ.04.033_440",
            "sku01615256692749" => "EZ.04.082_682",
            "sku11625056605475" => "EZ.04.082_682",
            "sku01619361129939" => "PH09006_257",
            "sku11620875364318" => "PH09006_257",
            "sku01622948066424" => "PH06027_001",
            "sku11622948066441" => "PH06027_001",
            "sku01619973225767" => "PH05013_443",
            "sku11622979944201" => "PH05013_443"
        ];

        foreach ($fobizSkuMapping as $fobizSkuCode => $m28SKuCode)
        {
            $sku = Sku::query()->where('code', $m28SKuCode)->first();
            if(!$sku instanceof Sku) {
                continue;
            }
            StoreSku::create([
                'tenant_id' => $store->tenant_id,
                'store_id' => $store->id,
                'marketplace_code' => $store->marketplace_code,
                'marketplace_store_id' => $store->marketplace_store_id,
                'sku_id' => $sku->id,
                'code' => $fobizSkuCode
            ]);
        }
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
