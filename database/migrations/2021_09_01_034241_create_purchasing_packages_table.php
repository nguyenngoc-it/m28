<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasingPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchasing_packages', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->default(0)->index();
            $table->integer('purchasing_order_id')->default(0)->index();
            $table->string('code')->index();
            $table->double('weight', 9, 3)->nullable()->comment('Cân nặng (kg)');
            $table->double('length', 6, 3)->nullable()->comment('Chiều dài (m)');
            $table->double('width', 6, 3)->nullable()->comment('Chiều rộng (m)');
            $table->double('height', 6, 3)->nullable()->comment('Chiều cao (m)');
            $table->string('status');
            $table->timestamps();

            $table->unique(['purchasing_order_id', 'code'], 'purchasing_packages_2');
        });

        Schema::create('purchasing_package_items', function (Blueprint $table) {
            $table->id();
            $table->integer('purchasing_package_id')->index();
            $table->integer('purchasing_variant_id')->default(0);
            $table->integer('quantity')->nullable();
            $table->timestamps();

            $table->unique(['purchasing_variant_id', 'purchasing_package_id'], 'purchasing_package_items_2');
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
