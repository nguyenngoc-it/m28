<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tenant_id')->index();
            $table->integer('creator_id')->index();
            $table->string('code')->index()->comment('Mã đơn nhập');
            $table->integer('stock')->nullable()->comment('Tổng số lượng tồn kho');
            $table->timestamps();
        });

        Schema::create('import_history_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tenant_id')->index();
            $table->integer('merchant_id')->index();
            $table->integer('import_history_id')->index();
            $table->integer('sku_id')->index();
            $table->integer('warehouse_id')->index();
            $table->integer('warehouse_area_id')->index();
            $table->integer('stock')->nullable()->comment('So luong ton kho');
            $table->string('freight_bill')->nullable()->index()->comment('Mã vận đơn');
            $table->string('package_code')->nullable()->index()->comment('Mã kiện');
            $table->text('note')->nullable()->comment('Ghi chu');
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
        Schema::dropIfExists('import_histories');
        Schema::dropIfExists('import_history_items');
    }
}
