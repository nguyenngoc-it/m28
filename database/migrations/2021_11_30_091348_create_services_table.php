<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->default(0)->index();
            $table->string('type', 100)->nullable()->comment('IMPORT, EXPORT, TRANSPORT')->index();
            $table->string('code', 100)->nullable()->index();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'type', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->drop();
        });
    }
}
