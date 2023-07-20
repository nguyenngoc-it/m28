<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tenant_id')->index();
            $table->string('code')->index()->comment('type of transaction');
            $table->string('name', 255);
            $table->string('contact', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('supplier_id')->nullable()->index()->comment('Nha cung cap');
        });

        Schema::table('skus', function (Blueprint $table) {
            $table->integer('supplier_id')->nullable()->index()->comment('Nha cung cap');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suppliers');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['supplier_id']);
        });

        Schema::table('skus', function (Blueprint $table) {
            $table->dropColumn(['supplier_id']);
        });
    }
}
