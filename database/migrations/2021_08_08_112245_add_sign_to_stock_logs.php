<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Stock\Models\StockLog;

class AddSignToStockLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->enum('change', [StockLog::CHANGE_INCREASE, StockLog::CHANGE_DECREASE])->after('stock_id');
            $table->string('sign')->nullable()->comment('Sign tương ứng với record data, dùng để check tính toàn vẹn của record data');
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
