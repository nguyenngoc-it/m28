<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;

class AddUserSystemUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'tenant_id' => 0,
            'username'   => User::USERNAME_SYSTEM,
            'password'   => 123123,
            'name'       => 'Hệ thống',
            'email'      => User::USERNAME_SYSTEM.'@app.com',
            'phone'      => '0123456799',
            'address'    => '',
        ]);
    }
}
