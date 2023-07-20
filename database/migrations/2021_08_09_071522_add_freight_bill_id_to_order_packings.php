<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFreightBillIdToOrderPackings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_packings', function (Blueprint $table) {
            $table->unsignedInteger('freight_bill_id')->after('order_id')->index()->comment('mã vận đơn hiện tại của ycdh');
            $table->index('shipping_partner_id');
            $table->index('merchant_id');
            $table->index('tenant_id');
            $table->index('order_id');
            $table->index('warehouse_id');
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_packings', function (Blueprint $table) {
            //
        });
    }
}
