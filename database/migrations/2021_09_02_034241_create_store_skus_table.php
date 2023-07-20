<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_skus', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->default(0)->index();
            $table->string('marketplace_code')->comment('SHOPEE, LAZADA, ...');
            $table->string('marketplace_store_id')->index()->comment('Định danh của store trên marketplace');
            $table->string('code')->comment('SKU code trên marketplace');
            $table->integer('sku_id')->index()->comment('SKU tương ứng trên M28');
            $table->timestamps();

            $table->unique(['code', 'marketplace_store_id', 'marketplace_code', 'tenant_id'], 'code');
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
