<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackageSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('package_skus', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('order_id')->index();
            $table->integer('package_id')->index();
            $table->integer('stock_id');
            $table->integer('sku_id')->index();
            $table->integer('warehouse_id')->index();
            $table->integer('warehouse_area_id')->index();
            $table->integer('quantity');
            $table->timestamps();

            $table->unique(['stock_id', 'package_id']);
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
