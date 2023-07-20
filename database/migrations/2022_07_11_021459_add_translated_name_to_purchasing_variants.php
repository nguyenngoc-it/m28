<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTranslatedNameToPurchasingVariants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchasing_variants', function (Blueprint $table) {
            $table->string('translated_name', 255)->after('name')->nullable();
        });
        Schema::table('purchasing_order_items', function (Blueprint $table) {
            $table->string('item_translated_name', 255)->after('item_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchasing_variants', function (Blueprint $table) {
            //
        });
    }
}
