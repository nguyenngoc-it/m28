<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('merchant_id')->index();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();

            $table->unique(['username', 'merchant_id']);
            $table->unique(['email', 'merchant_id']);
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
