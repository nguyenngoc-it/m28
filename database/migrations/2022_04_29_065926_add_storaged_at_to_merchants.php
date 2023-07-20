<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStoragedAtToMerchants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->timestamp('storaged_at')->nullable()->comment('Thời điểm bắt đầu sử dụng dịch vụ lưu kho');
            $table->unsignedInteger('free_days_of_storage')->nullable()->comment('Số ngày được miễn phí lưu kho');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchants', function (Blueprint $table) {
            //
        });
    }
}
