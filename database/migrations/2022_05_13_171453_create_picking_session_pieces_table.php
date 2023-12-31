<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreatePickingSessionPiecesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('picking_session_pieces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tenant_id')->nullable()->default(0);
            $table->unsignedInteger('picking_session_id')->nullable()->default(0);
            $table->unsignedInteger('order_id')->nullable()->default(0);
            $table->unsignedInteger('order_packing_id')->nullable()->default(0);
            $table->unsignedInteger('warehouse_id')->nullable()->default(0);
            $table->unsignedInteger('warehouse_area_id')->nullable()->default(0);
            $table->unsignedInteger('sku_id')->nullable()->default(0);
            $table->unsignedInteger('quantity')->nullable()->default(0)->comment('Số lượng sku trong lượt nhặt');
            $table->unsignedInteger('ranking')->nullable()->default(0)->comment('Thứ tự lượt nhặt hàng');
            $table->unsignedInteger('ranking_order')->nullable()->default(0)->comment('Thứ tự đơn hàng trong phiên nhặt hàng');
            $table->tinyInteger('is_picked')->nullable()->default(0)->comment('Đã kết thúc lượt nhặt hàng chưa');
            $table->nullableTimestamps();
            $table->unique(['picking_session_id', 'order_packing_id', 'warehouse_area_id', 'sku_id'], 'picking_session_id');
            $table->index('is_picked', 'is_picked');

            $table->charset   = 'utf8';
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
        Schema::dropIfExists('picking_session_pieces');
    }
}
