<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasingOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchasing_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->default(0)->index();
            $table->integer('purchasing_service_id')->default(0)->index();
            $table->integer('purchasing_account_id')->default(0)->index();
            $table->integer('merchant_id')->default(0)->index();
            $table->string('code');
            $table->string('m1_order_url')->nullable();
            $table->string('status');
            $table->string('marketplace')->comment('1688, taobao, ...');
            $table->string('image')->nullable();
            $table->string('supplier_code')->nullable()->index();
            $table->string('supplier_name')->nullable();
            $table->string('supplier_url')->nullable();
            $table->string('customer_username')->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('receiver_country_code')->nullable();
            $table->string('receiver_city_code')->nullable();
            $table->string('receiver_district_code')->nullable();
            $table->string('receiver_ward_code')->nullable();
            $table->string('receiver_address')->nullable();
            $table->text('receiver_note')->nullable();
            $table->integer('ordered_quantity')->nullable()->comment('Số lượng đặt');
            $table->integer('purchased_quantity')->nullable()->comment('Số lượng mua');
            $table->integer('received_quantity')->nullable()->comment('Số lượng kiểm');
            $table->string('currency')->nullable()->comment('Loại tiền tệ (CNY, USD, ...)');
            $table->double('exchange_rate', 15, 3)->nullable()->comment('Tỉ giá tiền tệ');
            $table->double('original_total_value', 15, 3)->nullable()->comment('Tiền hàng (đơn vị theo currency)');
            $table->double('total_value', 15, 3)->nullable()->comment('Tiền hàng');
            $table->double('total_fee', 15, 3)->nullable()->comment('Tổng phí');
            $table->double('grand_total', 15, 3)->nullable()->comment('Tổng số tiền');
            $table->double('total_paid', 15, 3)->nullable()->comment('Số tiền đã thanh toán');
            $table->double('total_unpaid', 15, 3)->nullable()->comment('Số tiền chưa thanh toán');
            $table->dateTime('ordered_at')->nullable()->index()->comment('Thời gian đặt hàng');
            $table->timestamps();

            $table->unique(['code', 'purchasing_service_id'], 'code');
        });

        Schema::create('purchasing_order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('purchasing_order_id')->index();
            $table->integer('purchasing_variant_id');
            $table->string('item_id')->nullable();
            $table->string('item_code')->nullable();
            $table->string('item_name')->nullable();
            $table->double('original_price', 15, 3)->nullable()->comment('Giá gốc trên marketplace');
            $table->double('price', 15, 3)->nullable()->comment('Giá gốc trên marketplace');
            $table->integer('ordered_quantity')->nullable()->comment('Số lượng đặt');
            $table->integer('purchased_quantity')->nullable()->comment('Số lượng mua');
            $table->integer('received_quantity')->nullable()->comment('Số lượng kiểm');
            $table->text('product_url')->nullable();
            $table->string('product_image')->nullable();
            $table->string('variant_image')->nullable();
            $table->text('variant_properties')->nullable()->comment('Danh sách thuộc tính, VD [id:123, name:Xanh, ...]');
            $table->timestamps();

            $table->unique(['purchasing_variant_id', 'purchasing_order_id'], 'purchasing_order_items');
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
