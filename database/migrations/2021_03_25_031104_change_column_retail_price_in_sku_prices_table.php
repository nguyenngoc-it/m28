<?php

use Illuminate\Database\Migrations\Migration;

class ChangeColumnRetailPriceInSkuPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE sku_prices CHANGE COLUMN retail_price retail_price DOUBLE(18,3) NULL DEFAULT NULL ;');
    }
}
