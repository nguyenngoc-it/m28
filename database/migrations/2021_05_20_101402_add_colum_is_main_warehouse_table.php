<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumIsMainWarehouseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("warehouses", function (Blueprint $table) {
            $table->boolean('is_main')
                ->index()->default(false)
                ->after('address')
                ->comment("La kho chinh cua mot quoc gia");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['is_main']);
        });
    }
}
