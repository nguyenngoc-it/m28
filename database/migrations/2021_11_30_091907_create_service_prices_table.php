<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->default(0)->index();
            $table->string('service_code', 100)->nullable()->index();
            $table->string('service_code_price', 100)->nullable()->index();
            $table->string('label', 255)->nullable();
            $table->double('price', 18, 2)->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'service_code', 'service_code_price']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_prices', function (Blueprint $table) {
            $table->drop();
        });
    }
}
