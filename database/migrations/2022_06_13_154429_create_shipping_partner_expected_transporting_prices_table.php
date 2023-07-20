<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreateShippingPartnerExpectedTransportingPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_partner_expected_transporting_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tenant_id')->nullable()->default(0);
            $table->double('max_weight', 15, 3)->unsigned()->nullable()->default(0.000)->comment('Cân nặng tối đa');
            $table->double('price', 15, 3)->unsigned()->nullable()->default(0.000)->comment('Mức giá cho cân nặng đó');
            $table->unsignedInteger('shipping_partner_id')->nullable()->default(0);
            $table->unsignedInteger('sender_ward_id')->nullable()->default(0);
            $table->unsignedInteger('sender_district_id')->nullable()->default(0);
            $table->unsignedInteger('sender_province_id')->nullable()->default(0);
            $table->unsignedInteger('receiver_ward_id')->nullable()->default(0);
            $table->unsignedInteger('receiver_district_id')->nullable()->default(0);
            $table->unsignedInteger('receiver_province_id')->nullable()->default(0);
            $table->string('sender_ward_code', 255)->nullable();
            $table->string('sender_district_code', 255)->nullable();
            $table->string('sender_province_code', 255)->nullable();
            $table->string('receiver_ward_code', 255)->nullable();
            $table->string('receiver_district_code', 255)->nullable();
            $table->string('receiver_province_code', 255)->nullable();
            $table->boolean('mapped')->nullable()->default(false)->index();
            $table->nullableTimestamps();
            $table->index('tenant_id', 'tenant_id');
            $table->index(['price', 'max_weight', 'shipping_partner_id'], 'price');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_partner_expected_transporting_prices');
    }
}
