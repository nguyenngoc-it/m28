<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFreightBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('freight_bills', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->index();
            $table->integer('shipping_partner_id')->nullable()->index();
            $table->integer('order_packing_id')->index();
            $table->integer('tenant_id')->index();
            $table->string('freight_bill_code');
            $table->string('status')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('receiver_address')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('sender_address')->nullable();
            $table->string('fee')->nullable();
            $table->text('snapshots')->nullable();
            $table->timestamps();

            $table->unique(['freight_bill_code', 'shipping_partner_id'], 'order_packing_freight_bill');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
