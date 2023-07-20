<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserIdentityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_identities', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('source')->comment('Từ nguồn nào (gobiz, facebook, google, ...)');
            $table->string('source_user_id')->index()->comment('ID của user trên site gốc');
            $table->text('source_user_info')->comment('Thông tin chi tiết của user trên site gốc');
            $table->string('access_token');
            $table->string('refresh_token')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'source']);
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
