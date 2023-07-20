<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnServiceComboIdToActiveCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('active_codes', function (Blueprint $table) {
            $table->unsignedInteger('service_combo_id')->after('tenant_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('active_codes', function (Blueprint $table) {
            //
        });
    }
}
