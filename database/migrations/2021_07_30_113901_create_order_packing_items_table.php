<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreateOrderPackingItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_packing_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_packing_id')->default(0);
            $table->unsignedInteger('order_stock_id')->default(0);
            $table->unsignedInteger('sku_id')->default(0);
            $table->double('price', 18, 6)->unsigned()->default(0.000000);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('quantity_packaged')->default(0)->comment('Số lượng sku đã đóng gói (ko hoàn trả về kho được)');
            $table->double('values', 18, 6)->unsigned()->default(0.000000)->comment('giá trị gói hàng');
            $table->unsignedInteger('stock_id')->default(0);
            $table->unsignedInteger('warehouse_id')->default(0);
            $table->unsignedInteger('warehouse_area_id')->default(0);
            $table->nullableTimestamps();

            $table->unique('order_stock_id');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_packing_items');
    }
}
