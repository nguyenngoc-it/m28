<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAliasToShippingPartners extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_partners', function (Blueprint $table) {
            $table->string('logo')->nullable();
            $table->json('alias')->nullable()->comment('những từ khoá để nhận biết dvvc');
        });
        Schema::create('location_searchs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('location_id')->index();
            $table->string('type',255);
            $table->string('parent_code',255);
            $table->string('keyword', 255)->comment('tu khoa search');
            $table->unique(['location_id', 'keyword']);
            $table->timestamps();
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
