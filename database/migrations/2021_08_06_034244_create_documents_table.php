<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->string('code')->nullable();
            $table->string('type');
            $table->string('status');
            $table->integer('warehouse_id')->index();
            $table->integer('creator_id')->index();
            $table->integer('verifier_id')->index()->nullable();
            $table->dateTime('verified_at')->index()->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['code', 'tenant_id']);
            $table->index('created_at');
        });

        Schema::create('document_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id')->index();
            $table->integer('order_id');
            $table->timestamps();

            $table->unique(['order_id', 'document_id']);
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
