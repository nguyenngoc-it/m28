<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('order_id')->index();
            $table->integer('warehouse_id')->index();
            $table->string('status');
            $table->double('weight', 9, 3)->nullable()->comment('Cân nặng (kg)');
            $table->double('length', 6, 3)->nullable()->comment('Chiều dài (m)');
            $table->double('width', 6, 3)->nullable()->comment('Chiều rộng (m)');
            $table->double('height', 6, 3)->nullable()->comment('Chiều cao (m)');
            $table->double('cod', 15, 3)->nullable()->comment('Tiền thu hộ');
            $table->string('delivery_note')->nullable()->comment('Ghi chú giao hàng');
            $table->string('freight_bill')->nullable()->index()->comment('Mã vận đơn');
            $table->integer('shipping_partner_id')->nullable()->index()->comment('Đối tác vận chuyển');
            $table->integer('creator_id')->nullable()->index()->comment('Người tạo');
            $table->timestamps();

            $table->index(['status', 'warehouse_id']);
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
