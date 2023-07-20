<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentFreightBillInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_freight_bill_inventories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_id')->index();
            $table->unsignedInteger('freight_bill_id')->index();
            $table->unsignedInteger('order_packing_id')->index();
            $table->unsignedInteger('order_id')->index();
            $table->string('freight_bill_code', 255)->comment('Mã vận đơn');

            $table->double('cod_total_amount', 15, 3)->nullable()->comment('Tổng tiền COD khách phải trả');
            $table->double('cod_paid_amount', 15, 3)->nullable()->comment('Số tiền COD khách đã trả');
            $table->double('cod_fee_amount', 15, 3)->nullable()->comment(' Phí COD');
            $table->double('shipping_amount', 15, 3)->nullable()->comment('Phí vận chuyển');
            $table->boolean('warning')->default(false)->comment('Cảnh báo đơn đã thu phí vận chuyển trước đó');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->integer('warehouse_id')->index('document_warehouse_index')->nullable()->default(null)->change();
            $table->integer('shipping_partner_id')->index()->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_freight_bill_inventories');
    }
}
