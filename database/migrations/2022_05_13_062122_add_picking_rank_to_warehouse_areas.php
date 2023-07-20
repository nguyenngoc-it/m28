<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPickingRankToWarehouseAreas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('warehouse_areas', function (Blueprint $table) {
            $table->unsignedInteger('picking_rank')->after('warehouse_id')->nullable()->default(100)
                ->comment('Thứ tự đường đi vị trí lấy hàng trong kho')
                ->index();
            $table->boolean('movable')->after('picking_rank')->nullable()->default(false)
                ->comment('Có phải vị trí di động, xe nhặt hàng ko');
        });

        Schema::table('order_packings', function (Blueprint $table) {
            $table->unsignedInteger('picker_id')->after('merchant_id')->nullable()
                ->comment('nhan vien nhat hang')
                ->index();
            $table->unsignedInteger('picking_session_id')->after('picker_id')->nullable()->default(null)
                ->default(0)->comment('phiên thực hiện nhặt hàng, gán ngay khi tạo ra phiên nhặt hàng, xoá đi nếu kết thúc phiên nhặt hàng mà đơn chưa nhặt xong');
            $table->unsignedInteger('pickup_truck_id')->nullable()->default(0)->comment('YCDH được nhặt hàng trên thiết bị nào, thực ra là vị trí kho di động');
            $table->timestamp('grant_picker_at')->nullable()->comment('Thời điểm gán nhân viên nhặt hàng');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('warehouse_areas', function (Blueprint $table) {
            //
        });
    }
}
