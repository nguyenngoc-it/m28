<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinanceServiceStatusToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('finance_service_status')->default(\Modules\Order\Models\Order::FINANCE_STATUS_UNPAID)->comment('Trạng thái đã thanh toán tài chính dịch vụ đóng hàng hay chưa');
        });

        Schema::table('document_freight_bill_inventories', function (Blueprint $table) {
            $table->string('finance_status_cod')->default(\Modules\Order\Models\Order::FINANCE_STATUS_UNPAID)->comment('Trạng thái đã thanh toán tài chính COD');
            $table->string('finance_status_fee')->default(\Modules\Order\Models\Order::FINANCE_STATUS_UNPAID)->comment('Trạng thái đã thanh toán tài chính chi phí');

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
            $table->dropColumn(['finance_service_status']);
        });
        Schema::table('document_freight_bill_inventories', function (Blueprint $table) {
            $table->dropColumn(['finance_status_cod', 'finance_status_fee']);
        });
    }
}
