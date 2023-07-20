<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostOfGoodsToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->double('cost_of_goods', 18, 3)->after('cost_price')->nullable()->comment('Giá vốn hàng hoá');
            $table->string('finance_cost_of_goods_status', 255)->after('cost_of_goods')->nullable()->comment('trạng thái thanh toán giá vốn hàng hoá');
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
            //
        });
    }
}
