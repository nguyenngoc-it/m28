<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackingTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packing_types', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::table('order_packings', function (Blueprint $table) {
            $table->integer('packing_type_id')->index()->nullable()->comment('Loại gói hàng là gì');
        });
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
