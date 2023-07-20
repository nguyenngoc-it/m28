<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeightToSkus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->double('weight', 9, 3)->nullable()->comment('Cân nặng (kg)');
        });

        Schema::table('order_packings', function (Blueprint $table) {
            $table->string('error_type')->nullable()->comment('Loại lỗi gì')->default('TECHNICAL');
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
