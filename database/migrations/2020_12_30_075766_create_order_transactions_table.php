<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('order_id')->index();
            $table->string('method')->comment('Phương thức thanh toán');
            $table->double('amount', 15, 3)->comment('Số tiền thanh toán');
            $table->dateTime('payment_time')->nullable()->comment('Thời gian thanh toán');
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
