<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('merchant_id')->index();
            $table->string('code');
            $table->string('status');
            $table->double('order_amount', 15, 3)->nullable()->comment('Tiền hàng');
            $table->double('discount_amount', 15, 3)->nullable()->comment('Số tiền chiết khấu');
            $table->double('shipping_amount', 15, 3)->nullable()->comment('Phí vận chuyển');
            $table->double('total_amount', 15, 3)->nullable()->comment('Tổng tiền khách phải trả');
            $table->double('paid_amount', 15, 3)->nullable()->comment('Số tiền khách đã trả');
            $table->double('debit_amount', 15, 3)->nullable()->comment('Số tiền khách còn thiếu');
            $table->string('receiver_name')->nullable()->comment('Tên người nhận');
            $table->string('receiver_phone')->nullable()->comment('Số điện thoại người nhận');
            $table->string('receiver_address')->nullable()->comment('Địa chỉ nhận');
            $table->string('receiver_note')->nullable()->comment('Ghi chú nhận');
            $table->dateTime('intended_delivery_at')->nullable()->comment('Ngày giao hàng dự kiến');
            $table->string('payment_type')->nullable()->comment('Hình thức thanh toán');
            $table->text('description')->nullable()->comment('Mô tả thêm cho đơn hàng');
            $table->integer('customer_id')->index();
            $table->integer('customer_address_id')->nullable()->index();
            $table->integer('sale_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['code', 'merchant_id']);
            $table->index(['status', 'merchant_id']);
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
