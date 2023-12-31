<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreateServiceComboMerchantStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_combo_merchant_statistics', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('merchant_id')->nullable()->default(0);
            $table->unsignedInteger('active_code_id')->nullable()->comment('Id Mã kích hoạt combo');
            $table->unsignedInteger('service_combo_id')->nullable()->default(0);
            $table->unsignedInteger('using_days')->nullable()->default(0);
            $table->unsignedInteger('using_skus')->nullable()->default(0);
            $table->unsignedInteger('service_combo_price_id')->nullable()->default(0);
            $table->unsignedInteger('quota')->nullable()->default(0);
            $table->nullableTimestamps();
            $table->unique(['merchant_id', 'service_combo_id', 'service_combo_price_id'], 'merchant_id');
            $table->index('using_days', 'using_days');
            $table->index('using_skus', 'using_skus');
            $table->index('quota', 'quota');

            $table->charset   = 'utf8mb4';
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
        Schema::dropIfExists('service_combo_merchant_statistics');
    }
}
