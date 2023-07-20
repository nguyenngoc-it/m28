<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierStatsColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->double('total_purchased_amount', 15, 3)->nullable()->comment('Tổng só tiền GD nhập kho');
            $table->double('total_sold_amount', 15, 3)->nullable()->comment('Tổng só tiền GD xuất kho');
            $table->double('total_paid_amount', 15, 3)->nullable()->comment('Tổng só tiền GD thanh toán');
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
