<?php

namespace Gobiz\Activity;

class Example
{
    public static function example()
    {
        ActivityService::log(new Activity([
            // Thông tin người thực hiện
            'creator' => [
                'id' => 111,
                'username' => 'admin',
                'name' => 'Full name',
                'tenant_id' => 222,
            ],

            // Mã action (format OBJECT.ACTION)
            'action' => 'USER.CREATE',

            // ID các đối tượng bị ảnh hưởng bới action (dùng để search ra các log activity của 1 đối tượng)
            'objects' => [
                'user' => 333,
            ],

            // Dữ liệu bổ sung (optional)
            'payload' => [
                'new_user' => [
                    'id' => 333,
                    'username' => 'user1',
                ],
            ],

            // Thời gian thực hiện (optional, default: now)
            'time' => new \DateTime(),
        ]));
    }
}