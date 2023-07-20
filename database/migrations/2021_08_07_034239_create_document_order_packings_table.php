<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentOrderPackingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_order_packings', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id')->index();
            $table->integer('order_packing_id');
            $table->timestamps();

            $table->unique(['order_packing_id', 'document_id']);
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
