<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('creator_id')->index()->comment('Người tạo');
            $table->string('code')->comment('Mã sản phẩm');
            $table->string('status')->comment('Trạng thái');
            $table->string('name')->comment('Tên sản phẩm');
            $table->text('description')->nullable()->comment('Mô tả sản phẩm');
            $table->string('image')->nullable()->comment('Ảnh đại diện sản phẩm');
            $table->string('images')->nullable()->comment('Danh sách ảnh sản phẩm');
            $table->integer('category_id')->nullable()->index()->comment('Danh mục');
            $table->integer('unit_id')->nullable()->index()->comment('Đơn vị');
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
