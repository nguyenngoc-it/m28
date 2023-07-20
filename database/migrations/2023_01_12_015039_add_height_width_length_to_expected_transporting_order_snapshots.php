<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHeightWidthLengthToExpectedTransportingOrderSnapshots extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expected_transporting_order_snapshots', function (Blueprint $table) {
            $table->unsignedDouble('height', 12, 3)->after('weight')->nullable();
            $table->unsignedDouble('width', 12, 3)->after('height')->nullable();
            $table->unsignedDouble('length', 12, 3)->after('width')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expected_transporting_order_snapshots', function (Blueprint $table) {
            //
        });
    }
}
