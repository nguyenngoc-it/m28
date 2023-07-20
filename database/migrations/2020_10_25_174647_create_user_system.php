<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\User\Models\User;
class CreateUserSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        User::create([
            'tenant_id' => 0,
            'username'   => User::USERNAME_SYSTEM,
            'password'   => '',
            'name'       => 'Hệ thống',
            'email'      => User::USERNAME_SYSTEM.'@app.com',
            'phone'      => '',
            'address'    => '',
        ]);
    }
}
