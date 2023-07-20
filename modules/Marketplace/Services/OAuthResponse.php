<?php

namespace Modules\Marketplace\Services;

class OAuthResponse
{
    /**
     * State được gửi khi tạo oauth url
     *
     * @var string
     */
    public $state;

    /**
     * Định danh của store trên marketplace
     *
     * @var string
     */
    public $storeId;

     /**
     * Name của store trên marketplace
     *
     * @var string
     */
    public $storeName = "";

    /**
     * Thông tin settings sẽ được lưu vào store
     *
     * @var array
     */
    public $settings = [];

    /**
     * Thông tin lỗi
     *
     * @var string
     */
    public $error;
}
