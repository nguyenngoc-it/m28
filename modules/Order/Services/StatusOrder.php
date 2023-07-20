<?php
namespace Modules\Order\Services;
use Modules\Order\Models\Order;

class StatusOrder
{
    /**
     * get Left Status
     * @param $status
     * @return array Status
     */
    public static function getBeforeStatus($status){
        if($status == ''){
            return array();
        }

        $status_array = array();
        $listStatus   = Order::$listStatus;

        $key = array_search($status, $listStatus);

        for ($i = $key-1 ; $i >= 0 ;$i--) {
            $status_array[] = $listStatus[$i];
        }

        return $status_array;
    }

    /**
     * get Right Status
     * @param $status
     * @return array Status
     */
    public static function getAfterStatus($status){
        if($status == ''){
            return array();
        }

        $status_array = array();
        $listStatus   = Order::$listStatus;

        $key = array_search($status,$listStatus);

        for ($i = $key+1 ; $i < count($listStatus) ;$i++) {
            $status_array[] = $listStatus[$i];
        }

        return $status_array;
    }

    /**
     * Check is left status in order
     * @param string $status
     * @param string $orderStatus
     * @return bool
     */
    public static function isLeftStatus($status, $orderStatus)
    {
        $afterStatus = self::getAfterStatus($status);

        if (empty($afterStatus)) {
            return false;
        }
        if (end($afterStatus) == $orderStatus) {
            return true;
        }
        return false;
    }

    /**
     * Is After Status
     * @param string $status
     * @param string $orderStatus
     * @param bool $includedCurrentStatus
     * @return bool
     */
    public static function isAfterStatus($status, $orderStatus, $includedCurrentStatus = false)
    {
        if ($includedCurrentStatus && $orderStatus == $status) {
            return true;
        }
        $afterStatus = self::getAfterStatus($status);

        if (empty($afterStatus)) {
            return false;
        }
        if (in_array($orderStatus, $afterStatus)) {
            return true;
        }
        return false;
    }

    /**
     * Is before status
     * @param $status
     * @param string $orderStatus
     * @param bool $includedCurrentStatus
     * @return bool
     */
    public static function isBeforeStatus($status, $orderStatus, $includedCurrentStatus = false)
    {
        if ($includedCurrentStatus && $orderStatus == $status) {
            return true;
        }

        $beforeStatus = self::getBeforeStatus($status);
        if (empty($beforeStatus)) {
            return false;
        }
        if (in_array($orderStatus, $beforeStatus)) {
            return true;
        }
        return false;
    }

    /**
     * Check current status is between start status and end status?
     * @param $startStatus
     * @param $endStatus
     * @param $orderStatus
     * @return bool
     */
    public static function isBetweenStatus($startStatus, $endStatus, $orderStatus)
    {
        $betweenStatus = self::getBetweenStatus($startStatus, $endStatus);
        if (in_array($orderStatus, $betweenStatus)) {
            return true;
        }

        return false;
    }

    /**
     * Get Between Status
     * @param $start
     * @param $end
     * @return array
     */
    public static function getBetweenStatus($start, $end){
        if($start == '' || $end == ''){
            return array();
        }

        if($start == $end) {
            return [$start];
        }

        $status_array = array();
        $listStatus   = Order::$listStatus;

        $key_start = array_search($start, $listStatus);
        $key_end   = array_search($end, $listStatus);

        if($key_start < $key_end){
            for ($i = $key_start ; $i <= $key_end ;$i++) {
                $status_array[] = $listStatus[$i];
            }
            return $status_array;
        }
        return array();
    }
}
