<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderExportingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_exportings', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('warehouse_id')->index();
            $table->integer('order_id')->index();
            $table->integer('shipping_partner_id')->index();
            $table->integer('freight_bill_id')->nullable()->index();
            $table->integer('order_packing_id')->nullable()->unique();
            $table->integer('creator_id')->index();
            $table->integer('handler_id')->nullable()->index();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('receiver_address')->nullable();
            $table->integer('total_quantity')->nullable()->comment('Tổng số lượng sản phẩm');
            $table->double('total_value', 15, 3)->nullable()->comment('Tổng giá trị sản phẩm');
            $table->string('status');
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('order_exporting_items', function (Blueprint $table) {
            $table->id();
            $table->integer('order_exporting_id')->index();
            $table->integer('sku_id');
            $table->double('price', 15, 3)->nullable()->comment('Đơn giá sản phẩm');
            $table->integer('quantity')->nullable()->comment('Số lượng sản phẩm');
            $table->double('value', 15, 3)->nullable()->comment('Giá trị sản phẩm');
            $table->timestamps();

            $table->unique(['sku_id', 'order_exporting_id']);
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
