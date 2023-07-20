<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnPackagedQuantityToOrderStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_stocks', function (Blueprint $table) {
            $table->integer('packaged_quantity')->default(0)->comment('Số lượng sku đã được đóng kiện');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_stocks', function (Blueprint $table) {
            $table->dropColumn('packaged_quantity');
        });
    }
}
