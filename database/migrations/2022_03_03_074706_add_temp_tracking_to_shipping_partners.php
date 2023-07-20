<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTempTrackingToShippingPartners extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_partners', function (Blueprint $table) {
            $table->json('temp_tracking')->nullable()->default(null)->comment('mẫu vận đơn để upload lên dvvc');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_partners', function (Blueprint $table) {
            //
        });
    }
}
