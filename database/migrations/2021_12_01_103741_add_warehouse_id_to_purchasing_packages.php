<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarehouseIdToPurchasingPackages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchasing_packages', function (Blueprint $table) {
            $table->integer('purchasing_order_id')->nullable()->change();
            $table->integer('destination_warehouse_id')->index()->nullable()->comment('Kho nhan');
            $table->integer('shipping_partner_id')->index()->nullable()->comment('DVVC');
            $table->string('freight_bill_code')->index()->nullable()->comment('Ma van don');
            $table->double('service_amount', 18, 2)->nullable()->comment('Tổng phí dịch vụ của kiện');
            $table->integer('creator_id')->index()->nullable()->comment('Nguoi tao');
            $table->integer('merchant_id')->index()->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('received_quantity')->nullable();
            $table->dateTime('imported_at')->nullable()->comment('Ngay nhap kho');
            $table->unique(['merchant_id', 'freight_bill_code'], 'freight_bill_unique');
        });

        Schema::table('purchasing_package_items', function (Blueprint $table) {
            $table->integer('purchasing_variant_id')->index()->nullable()->change();
            $table->integer('sku_id')->index()->nullable();
            $table->integer('received_quantity')->nullable();
            $table->string('note')->nullable()->comment('ghi chú');
            //$table->dropUnique('purchasing_package_items_2');
        });

        Schema::create('purchasing_package_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('service_id')->index();
            $table->unsignedInteger('service_price_id')->index();
            $table->unsignedInteger('purchasing_package_id')->index();
            $table->double('price', 18, 2)->nullable()->comment('Don gia');
            $table->integer('quantity')->nullable();
            $table->double('amount', 18, 2)->nullable()->comment('Thanh tien');
            $table->timestamps();
            $table->unique(['purchasing_package_id', 'service_price_id'], 'purchasing_package_service_unique');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchasing_packages', function (Blueprint $table) {
            //
        });
    }
}
