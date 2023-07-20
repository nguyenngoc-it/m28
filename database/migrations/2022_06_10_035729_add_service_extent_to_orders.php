<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceExtentToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->double('extent_service_expected_amount', 15, 3)->after('service_amount')->nullable()->default(0)
                ->comment('chi phí vận hành dự kiến');
            $table->double('extent_service_amount', 15, 3)->after('extent_service_expected_amount')->nullable()->default(0)
                ->comment('chi phí vận hành');
            $table->string('finance_extent_service_status')->after('extent_service_amount')->nullable()->default('UNPAID')
                ->comment('trạng thái tài chính chi phí mở rộng');
        });

        Schema::table('document_freight_bill_inventories', function (Blueprint $table) {
            $table->double('extent_amount', 15, 3)->after('shipping_amount')->nullable()->default(0)
                ->comment('chi phí mở rộng');
            $table->string('finance_status_extent')->nullable()->default('UNPAID')
                ->comment('trạng thái tài chính chi phí mở rộng');
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
            //
        });
    }
}
