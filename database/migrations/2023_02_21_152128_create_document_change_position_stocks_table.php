<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreateDocumentChangePositionStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_change_position_stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_id')->nullable();
            $table->unsignedInteger('stock_id_from')->nullable()->comment('Từ đâu');
            $table->unsignedInteger('stock_id_to')->nullable()->comment('tới đâu');
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('creator_id')->nullable();
            $table->nullableTimestamps();
            $table->unique(['document_id', 'stock_id_from', 'stock_id_to'], 'document_id');
            $table->index('quantity', 'quantity');
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_change_position_stocks');
    }
}