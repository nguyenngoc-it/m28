<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRealQuantityToStockLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->integer('sku_id')->nullable()->index()->after('stock_id');
            $table->integer('quantity')->nullable()->comment('Số lượng thay đổi của tồn tính toán')->change();
            $table->integer('real_quantity')->nullable()->after('quantity')->comment('Số lượng thay đổi của tồn thực tế');
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
