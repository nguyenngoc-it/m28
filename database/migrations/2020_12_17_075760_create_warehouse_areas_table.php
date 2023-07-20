<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_areas', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('warehouse_id');
            $table->string('code');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
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
