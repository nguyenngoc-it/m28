<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentSkuImportingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_sku_importings', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('document_id')->index();
            $table->integer('sku_id')->index();
            $table->integer('warehouse_id')->index();
            $table->integer('warehouse_area_id')->index();
            $table->integer('stock_id')->index();
            $table->integer('quantity')->comment('Số lượng quét');
            $table->integer('real_quantity')->comment('Số lượng thực nhận');

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
    }
}
