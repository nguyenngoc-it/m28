<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDropshipToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('dropship')->default(false)->comment('Đánh dấu là đơn dropship');
        });

        Schema::create('order_product_price_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id')->index();
            $table->unsignedInteger('product_price_id')->index();
            $table->unsignedInteger('product_price_detail_id')->index();
            $table->integer('sku_id')->nullable()->index()->comment('Sku trên M28 nếu type = SKU');

            $table->integer('combo')->nullable()->comment('Số lượng combo nếu type = COMBO');
            $table->double('cost_price', 18, 6)->nullable()->comment('Giá nhập');
            $table->double('service_packing_price', 18, 6)->nullable()->comment('Giá dịch vụ đóng gói');
            $table->double('service_shipping_price', 18, 6)->nullable()->comment('Giá dịch vụ vận chuyển');
            $table->double('total_price', 18, 6)->nullable()->comment('Tổng');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_product_price_details');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['dropship']);
        });
    }
}
