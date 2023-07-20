<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_skus', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('order_id')->index();
            $table->integer('sku_id')->index();
            $table->double('tax', 5, 2)->nullable()->comment('Thuế (%)');
            $table->double('price', 18, 6)->comment('Đơn giá');
            $table->integer('quantity')->comment('Số lượng');
            $table->double('order_amount', 15, 3)->nullable()->comment('Tiền hàng');
            $table->double('discount_amount', 15, 3)->nullable()->comment('Số tiền chiết khấu');
            $table->double('total_amount', 15, 3)->nullable()->comment('Tổng tiền khách phải trả');
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
    }
}
