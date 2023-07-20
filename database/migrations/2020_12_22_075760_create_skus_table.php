<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('skus', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('product_id')->index();
            $table->integer('unit_id')->nullable()->index();
            $table->integer('category_id')->nullable()->index()->comment('Danh mục');
            $table->integer('creator_id')->index()->comment('Người tạo');
            $table->string('status')->comment('Trạng thái');
            $table->string('code');
            $table->string('name')->nullable();
            $table->string('barcode')->nullable()->index();
            $table->text('options')->nullable()->comment('Thuộc tính');
            $table->double('tax', 5, 2)->nullable()->comment('Thuế (%)');
            $table->double('cost_price', 18, 6)->nullable()->comment('Giá nhập');
            $table->double('wholesale_price', 18, 6)->nullable()->comment('Giá bán buôn');
            $table->double('retail_price', 18, 6)->nullable()->comment('Giá bán lẻ');
            $table->integer('stock')->nullable()->comment('Tổng tồn kho tạm tính');
            $table->timestamps();

            $table->unique(['code', 'tenant_id']);
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
