<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Product\Models\Sku;
class CreateWarehouseStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->default(0)->index();
            $table->integer('product_id')->index()->comment('Product trên M28');
            $table->integer('sku_id')->index()->comment('SKU trên M28');
            $table->integer('warehouse_id')->index();
            $table->string('sku_status')->default(Sku::STATUS_ON_SELL)->comment('Trạng thái sku có đang bán hay ngừng bán');
            $table->integer('quantity')->default(0)->comment('Số lượng tồn dự kiến');
            $table->integer('real_quantity')->default(0)->comment('Số lượng tồn thực tế');
            $table->integer('purchasing_quantity')->default(0)->comment(' Số lượng đang nhập thêm');
            $table->integer('packing_quantity')->default(0)->comment('Sô lượng đang đóng hàng');
            $table->integer('saleable_quantity')->default(0)->comment('Số lượng có thể bán (tiên lượng hàng)');
            $table->integer('min_quantity')->nullable()->default(null)->comment('Số lượng tồn tối thiểu trong 1 kho');
            $table->boolean('out_of_stock')->default(false)->comment('Đánh dấu có đang hết/thiếu hàng hay không');

            $table->timestamps();

            $table->unique(['sku_id', 'warehouse_id']);
        });

        Schema::table('purchasing_orders', function (Blueprint $table) {
            $table->integer('warehouse_id')->after('merchant_id')->nullable()->index();
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
