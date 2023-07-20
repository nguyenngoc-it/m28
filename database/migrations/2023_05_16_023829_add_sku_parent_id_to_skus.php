<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Product\Models\BatchOfGood;

class AddSkuParentIdToSkus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->unsignedInteger('sku_parent_id')->after('id')->nullable()->index()->comment('sku cha sinh ra sku lo');
            $table->unsignedInteger('batch_of_good_id')->after('sku_parent_id')->nullable()->index()
                ->comment('lo cua sku');
            $table->boolean('is_batch')->nullable()->default(false)->comment('sku quản lý theo lo ko');
            $table->string('logic_batch')->nullable()->index()->comment('logic quan ly lo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('skus', function (Blueprint $table) {
            //
        });
    }
}
