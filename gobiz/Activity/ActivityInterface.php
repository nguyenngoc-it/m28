<?php

namespace Gobiz\Activity;

use DateTimeInterface;

interface ActivityInterface
{
    /**
     * Get log id
     *
     * @return string|null
     */
    public function getId();

    /**
     * Lấy thông tin người thực hiện
     *
     * @return ActivityCreatorInterface
     */
    public function getCreator();

    /**
     * Lấy action thực hiện
     *
     * @return string
     */
    public function getAction();

    /**
     * Lấy danh sách các đối tượng bị ảnh hưởng bởi hành động
     *
     * @return array
     */
    public function getObjects();

    /**
     * Lấy dữ liệu bổ sung
     *
     * @return array
     */
    public function getPayload();

    /**
     * Lấy message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Có public log hay không
     *
     * @return boolean
     */
    public function getPublic();

    /**
     * Lấy thời gian thực hiện
     *
     * @return DateTimeInterface
     */
    public function getTime();

    /**
     * Lấy dữ liệu activity dạng array
     *
     * @return array
     */
    public function getActivityAsArray();
}