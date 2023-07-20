<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPackingServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_packing_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('service_id')->index();
            $table->unsignedInteger('service_price_id')->index();
            $table->unsignedInteger('order_packing_id');
            $table->unsignedInteger('order_id')->index();
            $table->double('price', 18, 2)->nullable()->comment('Don gia');
            $table->integer('quantity')->nullable();
            $table->double('amount', 18, 2)->nullable()->comment('Thanh tien');
            $table->timestamps();
            $table->unique(['order_packing_id', 'service_price_id'], 'order_packing_service_unique');
        });

        Schema::table('order_packings', function (Blueprint $table) {
            $table->integer('packing_type_id')->nullable()->default(null)->change();
            $table->double('service_amount', 18, 2)->nullable()->comment('Tong tien dich vu');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_packing_services', function (Blueprint $table) {
            $table->drop();
        });
    }
}
