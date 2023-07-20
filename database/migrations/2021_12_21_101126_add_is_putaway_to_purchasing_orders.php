<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPutawayToPurchasingOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchasing_orders', function (Blueprint $table) {
            $table->boolean('is_putaway')->default(false)->after('grand_total')->comment('Đánh dấu đơn nhập có về kho hay không?');
        });
        Schema::table('purchasing_packages', function (Blueprint $table) {
            $table->boolean('is_putaway')->default(false)->index()->comment('Đánh dấu kiện nhập có về kho hay không?');
        });
        Schema::create('purchasing_order_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->default(0)->index();
            $table->unsignedInteger('service_id')->default(0)->index();
            $table->unsignedInteger('service_price_id')->default(0)->index();
            $table->unsignedInteger('purchasing_order_id')->default(0)->index();
            $table->timestamps();

            $table->unique(['service_price_id', 'purchasing_order_id'], 'unique_2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchasing_orders', function (Blueprint $table) {
            //
        });
    }
}
