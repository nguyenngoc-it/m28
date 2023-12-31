<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreateServiceComboPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_combo_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('service_combo_id')->nullable()->default(0);
            $table->unsignedInteger('service_price_id')->nullable()->default(0);
            $table->string('type', 255)->nullable()->comment('nhóm vd');
            $table->unsignedInteger('service_id')->nullable()->default(0);
            $table->unsignedInteger('quota')->nullable()->default(0)->comment('số lần sử dụng');
            $table->nullableTimestamps();
            $table->unique(['service_combo_id', 'service_id'], 'service_combo_id');
            $table->index('type', 'type');
            $table->index('quota', 'quota');
            $table->index('service_price_id', 'service_price_id');
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_combo_prices');
    }
}
