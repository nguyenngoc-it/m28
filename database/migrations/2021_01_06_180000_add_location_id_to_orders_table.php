<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLocationIdToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("orders", function (Blueprint $table){
            $table->integer('receiver_country_id')->nullable()->index();
            $table->integer('receiver_province_id')->nullable()->index();
            $table->integer('receiver_district_id')->nullable()->index();
            $table->integer('receiver_ward_id')->nullable()->index();
            $table->text('extra_services')->nullable()->comment('Dich vu');
            $table->integer('customer_id')->nullable()->change();
        });

        Schema::table("order_transactions", function (Blueprint $table){
            $table->string('bank_name')->nullable()->comment('Ten ngan hang');
            $table->string('bank_account')->nullable()->comment('So tai khoan');
            $table->string('note')->nullable()->comment('Ghi chu');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("orders", function (Blueprint $table){
            $table->dropColumn(['extra_services', 'receiver_country_id', 'receiver_province_id', 'receiver_district_id', 'receiver_ward_id']);
        });

        Schema::table("order_transactions", function (Blueprint $table){
            $table->dropColumn(['bank_name', 'bank_account', 'note']);
        });
    }
}



