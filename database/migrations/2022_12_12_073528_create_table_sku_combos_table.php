<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableSkuCombosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sku_combos', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->integer('tenant_id');
            $table->integer('category_id')->nullable();
            $table->integer('merchant_id');
            $table->string('source')->nullable();
            $table->float('price')->nullable();
            $table->string('status');
            $table->json('snap_sku')->nullable();
            $table->mediumText('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sku_combos', function (Blueprint $table) {
            //
        });
    }
}
