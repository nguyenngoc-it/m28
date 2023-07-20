<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnFreightBillToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('freight_bill')->nullable()->index()->comment('Mã vận đơn');
            $table->string('campaign')->nullable()->comment('campaign');
            $table->dateTime('created_at_origin')->nullable()->comment('Ngày phát sinh đơn hàng');
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
            $table->dropColumn(['freight_bill', 'campaign', 'created_at_origin']);
        });
    }
}
