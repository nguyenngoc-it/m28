<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->index();
            $table->string('label')->comment('Tên thuộc tính');
            $table->timestamps();
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->index();
            $table->integer('product_option_id')->index();
            $table->string('label')->comment('Giá trị thuộc tính');
            $table->timestamps();
        });

        Schema::create('sku_option_values', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sku_id')->index();
            $table->integer('product_option_value_id');
            $table->unique(['product_option_value_id','sku_id']);
            $table->timestamps();
        });

        Schema::table('skus', function (Blueprint $table) {
            $table->softDeletes(); // add
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('sku_option_values');
    }
}
