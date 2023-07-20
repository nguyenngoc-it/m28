<?php

use Illuminate\Database\Migrations\Migration;

class AddLocations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Modules\Location\Models\Location::create([
           'code' => 'F100376',
           'type' => 'DISTRICT',
           'parent_code' => 'F49702',
           'label' => 'Nong Saeng 41340',
           'detail' => '',
           'active' => true,
       ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
