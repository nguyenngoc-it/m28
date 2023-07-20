<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreateStorageFeeMerchantStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('storage_fee_merchant_statistics', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('merchant_id')->nullable();
            $table->timestamp('closing_time')->nullable()->comment('ngày lưu kho');
            $table->double('fee', 16, 2)->nullable()->comment('phí lưu kho trong ngày');
            $table->double('fee_paid', 16, 2)->nullable()->comment('phí lưu kho đã thu trong ngày');
            $table->string('trans_m4_id',255)->nullable()->comment('trans đã thực hiện thu phí từ M4');
            $table->double('total_volume', 10, 4)->nullable()->comment('tổng thể tích tính phí lưu kho');
            $table->unsignedInteger('total_sku')->nullable()->comment('số lượng sku tính phí lưu kho');
            $table->nullableTimestamps();

            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('storage_fee_merchant_statistics');
    }
}
