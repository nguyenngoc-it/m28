<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemarkToOrderPackings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_packings', function (Blueprint $table) {
            $table->string('remark', 255)->after('status')->nullable()->collation('utf8_vietnamese_ci')->index();
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
