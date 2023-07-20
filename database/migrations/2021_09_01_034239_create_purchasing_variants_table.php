<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasingVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchasing_variants', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->string('marketplace')->comment('1688, taobao, ...')->nullable();
            $table->string('variant_id')->comment('Định danh của biến thể trên marketplace')->nullable();
            $table->integer('sku_id')->nullable()->index()->comment('SKU tương ứng của biến thể trên m28');
            $table->string('code')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->text('properties')->nullable()->comment('Danh sách thuộc tính, VD [id:123, name:Xanh, ...]');
            $table->text('product_url')->nullable();
            $table->string('product_image')->nullable();
            $table->string('supplier_code')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('supplier_url')->nullable();
            $table->string('spec_id')->nullable();
            $table->text('payload')->nullable();
            $table->timestamps();

            $table->unique(['variant_id', 'marketplace', 'tenant_id']);
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
