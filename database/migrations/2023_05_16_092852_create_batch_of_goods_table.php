<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreateBatchOfGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('batch_of_goods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sku_id')->nullable()->comment('sku có quản lý lô');
            $table->unsignedInteger('sku_child_id')->nullable()->comment('sku sinh ra do quản lý lô');
            $table->string('code', 255)->nullable();
            $table->double('cost_of_goods', 18,3)->nullable()->comment('giá vốn');
            $table->timestamp('production_at')->nullable()->comment('ngày sản xuất');
            $table->timestamp('expiration_at')->nullable()->comment('ngày hết hạn');
            $table->nullableTimestamps();
            $table->unique(['sku_id', 'code'], 'sku_id');
            $table->unique('sku_child_id', 'sku_child_id');
            $table->index('code', 'code');
            $table->index('production_at', 'production_at');
            $table->index('expiration_at', 'expiration_at');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('batch_of_goods');
    }
}
