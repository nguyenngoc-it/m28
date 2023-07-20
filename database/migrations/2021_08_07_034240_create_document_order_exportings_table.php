<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentOrderExportingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_order_exportings', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id')->index();
            $table->integer('order_exporting_id');
            $table->timestamps();

            $table->unique(['order_exporting_id', 'document_id']);
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
