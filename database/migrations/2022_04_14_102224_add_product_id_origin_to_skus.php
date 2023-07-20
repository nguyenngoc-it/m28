<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductIdOriginToSkus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_id_origin')->index()->nullable()->comment('id sản phẩm gốc bên web đối tác, ví dụ shopee');
            $table->string('sku_id_origin')->index()->nullable()->comment('id biến thể gốc bên web đối tác, ví dụ shopee');
        });

        Schema::table('skus', function (Blueprint $table) {
            $table->string('product_id_origin')->index()->nullable()->comment('id sản phẩm gốc bên web đối tác, ví dụ shopee');
            $table->string('sku_id_origin')->index()->nullable()->comment('id biến thể gốc bên web đối tác, ví dụ shopee');
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
            $table->dropColumn(['product_id_origin', 'sku_id_origin']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['product_id_origin', 'sku_id_origin']);
        });
    }
}
