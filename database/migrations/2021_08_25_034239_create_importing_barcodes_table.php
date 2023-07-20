<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportingBarcodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importing_barcodes', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('document_id')->index();
            $table->string('type')->comment('SKU_CODE|SKU_REF|PACKAGE_CODE|ORDER_CODE');
            $table->string('barcode')->comment('Mã được quét');

            $table->unique(['barcode', 'type', 'document_id']);
            $table->timestamps();
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