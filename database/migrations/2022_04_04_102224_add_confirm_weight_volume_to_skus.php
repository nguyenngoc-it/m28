<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfirmWeightVolumeToSkus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->boolean('confirm_weight_volume')->after('height')->nullable()->comment('van hanh da xac nhan can nang va kich thuoc sku hay chua');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('skus', function (Blueprint $table) {
            //
        });
    }
}
