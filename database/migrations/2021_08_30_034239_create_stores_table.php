<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Store\Models\Store;

class CreateStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('merchant_id')->index();
            $table->string('marketplace_code')->comment('SHOPEE, LAZADA, ...');
            $table->string('marketplace_store_id')->comment('Định danh của store trên marketplace');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->text('settings')->nullable();
            $table->string('product_sync')->nullable();
            $table->string('order_sync')->nullable();
            $table->string('status')->default(Store::STATUS_ACTIVE);
            $table->timestamps();

            $table->unique(['marketplace_store_id', 'marketplace_code', 'tenant_id'], 'marketplace_store_id');
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
