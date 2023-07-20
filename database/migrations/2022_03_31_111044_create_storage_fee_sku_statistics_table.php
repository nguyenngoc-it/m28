<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreateStorageFeeSkuStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('storage_fee_sku_statistics', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('merchant_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('stock_id')->nullable();
            $table->unsignedInteger('sku_id')->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->unsignedInteger('warehouse_area_id')->nullable();
            $table->unsignedInteger('service_price_id')->nullable()->comment('đơn giá áp dụng tại thời điểm tính phí');
            $table->double('volume', 10, 4)->nullable()->comment('thể tích m2 của sku tại thời điểm tính phí');
            $table->unsignedInteger('quantity')->nullable()->comment('số lượng đang tồn kho thực tế');
            $table->timestamp('closing_time')->nullable()->comment('thời điểm chốt thu phí lưu kho, được sử dụng để hiển thị ngày tính phí lưu kho');
            $table->double('fee', 16, 2)->nullable()->comment('chi phí lưu kho trong ngày');
            $table->nullableTimestamps();
            $table->unique(['merchant_id', 'product_id', 'stock_id', 'closing_time'], 'merchant_id');
            $table->index('merchant_id', 'merchant_id_2');
            $table->index('product_id', 'product_id');
            $table->index('stock_id', 'stock_id');
            $table->index('sku_id', 'sku_id');
            $table->index('service_price_id', 'service_price_id');

            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('storage_fee_sku_statistics');
    }
}