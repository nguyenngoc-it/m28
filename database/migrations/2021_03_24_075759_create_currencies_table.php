<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->integer('precision')->default(0);
            $table->string('format')->comment('Format tien te vi du: ฿{amount}');
            $table->string('thousands_separator')->default(',');
            $table->string('decimal_separator')->default('.');
            $table->timestamps();
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->integer('currency_id')->nullable()->index()->comment('Id tiền tệ');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->integer('currency_id')->nullable()->index()->comment('Id tiền tệ');
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
