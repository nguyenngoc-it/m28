<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHeightWidthLengthToOrderPackings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_packings', function (Blueprint $table) {
            $table->unsignedFloat('height')->nullable();
            $table->unsignedFloat('width')->nullable();
            $table->unsignedFloat('length')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_packings', function (Blueprint $table) {
            //
        });
    }
}
