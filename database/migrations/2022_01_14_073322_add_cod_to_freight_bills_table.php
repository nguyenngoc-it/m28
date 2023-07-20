<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCodToFreightBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('freight_bills', function (Blueprint $table) {
            $table->double('cod_total_amount', 15, 3)->nullable()->comment('Tổng tiền COD khách phải trả');
            $table->double('cod_paid_amount', 15, 3)->nullable()->comment('Số tiền COD khách đã trả');
            $table->double('cod_fee_amount', 15, 3)->nullable()->comment(' Phí COD');
            $table->double('shipping_amount', 15, 3)->nullable()->comment('Phí vận chuyển');
            $table->double('other_fee', 15, 3)->nullable()->comment('Phí khác');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->double('cod_fee_amount', 15, 3)->nullable()->comment(' Phí COD');
            $table->double('other_fee', 15, 3)->nullable()->comment('Phí khác');
        });

        Schema::table('document_freight_bill_inventories', function (Blueprint $table) {
            $table->double('other_fee', 15, 3)->nullable()->comment('Phí khác');
            $table->string('status')->comment('Trạng thái hợp lệ hoặc không correct|incorrect');
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
