<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNullablePriceToOrderSkus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_skus', function (Blueprint $table) {
            $table->decimal('price', 18, 6)->nullable()->default(null)->comment('Đơn giá')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_skus', function (Blueprint $table) {
            //
        });
    }
}
