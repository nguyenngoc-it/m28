<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnColorToSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->integer('product_id')->nullable()->change();
            $table->string('color')->nullable()->comment('Màu sắc');
            $table->string('size')->nullable()->comment('Kích thước');
            $table->string('type')->nullable()->comment('Phân loại');
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
            $table->integer('order_id')->index();
            $table->dropColumn(['color', 'size', 'type']);
        });
    }
}
