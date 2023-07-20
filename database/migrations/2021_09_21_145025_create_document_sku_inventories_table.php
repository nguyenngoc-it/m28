<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreateDocumentSkuInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_sku_inventories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_id')->default(0);
            $table->unsignedInteger('sku_id')->default(0);
            $table->integer('quantity_in_stock')->default(0)->comment('Số lượng có trong kho (hệ thống)');
            $table->integer('quantity_checked')->nullable()->comment('Số lượng kiểm được');
            $table->integer('quantity_balanced')->nullable()->comment('Số lượng so sánh');
            $table->string('explain', 255)->nullable()->comment('Ghi chú / giải thích');
            $table->nullableTimestamps();
            $table->unique(['document_id', 'sku_id'], 'document_id');
            $table->index('document_id', 'document_id_2');
            $table->index('sku_id', 'sku_id');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_sku_inventories');
    }
}