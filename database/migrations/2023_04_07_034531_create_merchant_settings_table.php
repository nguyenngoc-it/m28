<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchantSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('merchant_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['merchant_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_settings');
    }
}
