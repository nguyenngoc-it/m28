<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceToProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('source')->nullable()->default(null);
        });

        Schema::table('skus', function (Blueprint $table) {
            $table->mediumText('images')->nullable()->comment('Danh sách ảnh sản phẩm');
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
            $table->dropColumn(['images']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['source']);
        });
    }
}
