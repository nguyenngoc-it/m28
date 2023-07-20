<?php

use Gobiz\Database\MongoMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePricesTable extends MongoMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->default(0)->index();
            $table->unsignedInteger('creator_id')->default(0)->index();
            $table->unsignedInteger('product_id')->index()->comment('Product trên M28');
            $table->string('type')->index()->comment('Loại báo giá: COMBO, SKU');
            $table->string('status')->index()->comment('Trạng thái: WAITING_CONFIRM, ACTIVE, CANCELED');
            $table->timestamps();
        });

        Schema::create('product_price_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->default(0)->index();
            $table->unsignedInteger('product_price_id')->default(0)->index();
            $table->integer('combo')->nullable()->index()->comment('Số lượng combo nếu type = COMBO');
            $table->integer('sku_id')->nullable()->index()->comment('Sku trên M28 nếu type = SKU');

            $table->double('cost_price', 18, 6)->nullable()->comment('Giá nhập');
            $table->double('service_packing_price', 18, 6)->nullable()->comment('Giá dịch vụ đóng gói');
            $table->double('service_shipping_price', 18, 6)->nullable()->comment('Giá dịch vụ vận chuyển');
            $table->double('total_price', 18, 6)->nullable()->comment('Tổng');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(['product_prices', 'product_price_details']);
    }
}
