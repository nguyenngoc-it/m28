<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPackedAtToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('packer_id')->index()->default(0)->comment('người tạo xác nhận đóng hàng');
            $table->dateTime('packed_at')->index()->nullable()->comment('Thời gian đóng gói hàng - ngày xác nhận đóng hàng');

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
            $table->dropColumn(['packed_at']);
        });
    }
}
