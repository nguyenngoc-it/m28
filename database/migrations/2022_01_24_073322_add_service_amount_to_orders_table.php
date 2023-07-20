<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceAmountToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->double('service_amount', 15, 3)->nullable()->comment('Dịch vụ đóng gói');
            $table->double('amount_paid_to_seller', 15, 3)->nullable()->comment('Số tiền công nợ trả cho seller');
            $table->index(['created_at', 'tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_amount', 'amount_paid_to_seller']);
        });
    }
}
