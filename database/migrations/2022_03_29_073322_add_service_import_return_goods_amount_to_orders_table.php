<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceImportReturnGoodsAmountToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->double('service_import_return_goods_amount', 15, 3)->nullable()->comment('Dịch vụ nhập hàng hoàn');
            $table->string('finance_service_import_return_goods_status')->default(\Modules\Order\Models\Order::FINANCE_STATUS_UNPAID)->comment('Trạng thái đã thanh toán tài chính dịch vụ nhập hàng hoàn hay chưa');
        });

        Schema::create('order_import_return_goods_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('service_id')->index();
            $table->unsignedInteger('service_price_id')->index();
            $table->unsignedInteger('order_id')->index();
            $table->double('price', 18, 2)->nullable()->comment('Don gia');
            $table->timestamps();
            $table->unique(['order_id', 'service_price_id'], 'order_import_return_goods_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_import_return_goods_amount', 'finance_service_import_return_goods_status']);
        });

        Schema::dropIfExists('order_import_return_goods_services');
    }
}
