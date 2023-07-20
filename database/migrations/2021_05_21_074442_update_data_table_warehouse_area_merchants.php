<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDataTableWarehouseAreaMerchants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $warehouseArea = \Modules\Warehouse\Models\WarehouseArea::query()
            ->where("merchant_id", "<>", 0)
            ->get(['id AS warehouse_area_id', 'merchant_id'])
            ->toArray();
        \Modules\Warehouse\Models\WarehouseAreaMerchant::query()->insert($warehouseArea);

        Schema::table('warehouse_areas', function (Blueprint $table) {
            $table->integer('merchant_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
