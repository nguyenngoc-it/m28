<?php

namespace Gobiz\Support;

use Carbon\Carbon;
use Closure;
use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\Utils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class Helper
{
    /**
     * Xoa cac ky tu dac biet cua chuoi
     *
     * @param $string
     * @return null|string|string[]
     */
    public static function clean($string)
    {
        $string = str_replace(' ', '', $string);
        $string = str_replace('-', '', $string);
        return preg_replace('/[^A-Za-z0-9]/', '', $string);
    }

    /**
     * @param $str
     * @return string|string[]|null
     */
    public static function convert_vi_to_en($str)
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
        $str = preg_replace("/(đ)/", "d", $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", "A", $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", "E", $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", "I", $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", "O", $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", "U", $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", "Y", $str);
        $str = preg_replace("/(Đ)/", "D", $str);
        return trim($str);
    }

    /**
     * @param integer $limit
     * @return bool|string
     */
    public static function unique_code(int $limit)
    {
        return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
    }

    /**
     * @param $array
     * @param $key
     * @return mixed|null
     */
    public static function issetNull($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }

    /**
     * @return string
     */
    public static function getFakeFullName()
    {
        $first            = ['Nguyễn', 'Vũ', 'Đặng', 'Lương', 'Phạm', 'Hoàng', 'Trịnh', 'Phan', 'Lê', 'Mạc', 'Hồ', 'Trương',
            'Lý', 'Tô', 'Mai', 'Tòng', 'Cung', 'Văn', 'Đinh', 'Vương', 'Đoàn', 'Hà'];
        $middleLast[0][0] = ['Hồng', 'Minh', 'Gia', 'Ngọc', 'Quang', 'Thanh', 'Ngân', 'Giang', 'Uyên', 'Trúc', 'Như', 'Thị', 'Y'];
        $middleLast[1][0] = ['Văn', 'Hồng', 'Minh', 'Gia', 'Ngọc', 'Quang', 'Thanh', 'Sơn', 'Huy', 'Ngân', 'Giang', 'Trúc', 'Như'];
        $middleLast[0][1] = [
            'Oanh', 'Trang', 'Tú Anh', 'Thùy Anh', 'Kim Chi', 'Mỹ Châu', 'Diệp Anh', 'Huệ', 'Phương', 'Quỳnh',
            'Như Lan', 'Ngọc Mai', 'Ái Nhi', 'Lê', 'Đào', 'Hồng', 'Trúc', 'Xu', 'Trâm', 'Châu', 'Phi', 'Vân', 'Tuyết', 'Ánh', 'Thu', 'Minh'
        ];
        $middleLast[1][1] = [
            'Hà', 'Bình', 'Tú', 'Tùng', 'Vũ', 'Thanh', 'Kim', 'Châu', 'Anh', 'Phương', 'Quỳnh',
            'Quân', 'Đức', 'Chiến', 'Sáng', 'Đông', 'Bảo', 'Hải', 'Việt', 'Duy', 'Tâm', 'Thái', 'Lâm', 'Bách', 'Khánh', 'Sơn', 'Kiên'
        ];
        $manOrWoman       = rand(0, 1);


        return $first[rand(0, count($first) - 1)] . ' ' . $middleLast[$manOrWoman][0][rand(0, count($middleLast[$manOrWoman][0]) - 1)] . ' ' .
            $middleLast[$manOrWoman][1][rand(0, count($middleLast[$manOrWoman][1]) - 1)];
    }

    /**
     * @return string
     */
    public static function getFakeNumberPhone()
    {
        $first      = ['0165', '0166', '0168', '090', '091', '092', '093', '086', '097', '098'];
        $characters = '0123456789';
        $last       = '';

        for ($i = 0; $i < 7; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $last  .= $characters[$index];
        }

        return $first[rand(0, count($first) - 1)] . $last;
    }

    /**
     * @param $dateTo
     * @param $dateFrom
     * @param bool $roundDay
     * @return int
     */
    public static function dateDiff(DateTime $dateTo, DateTime $dateFrom, $roundDay = true)
    {
        if (!$dateTo instanceof Carbon) {
            $dateTo = Carbon::parse($dateTo->format('Y-m-d H:i:s'));
        }
        if (!$dateFrom instanceof Carbon) {
            $dateFrom = Carbon::parse($dateFrom->format('Y-m-d H:i:s'));
        }

        $secondOnDayTo   = $dateTo->hour * 3600 + $dateTo->minute * 60 + $dateTo->second;
        $secondOnDayFrom = $dateFrom->hour * 3600 + $dateFrom->minute * 60 + $dateFrom->second;

        $dateDiff = date_diff($dateTo, $dateFrom);
        if ($dateDiff instanceof DateInterval) {
            if ($roundDay) {
                return $secondOnDayTo > $secondOnDayFrom ? $dateDiff->days : $dateDiff->days + 1;
            }

            return $dateDiff->days;
        }

        return 0;
    }

    /**
     * @param Builder $builder
     * @return string
     */
    public static function renderSql(Builder $builder)
    {
        $sql = str_replace(['?'], ['\'%s\''], $builder->toSql());
        return vsprintf($sql, $builder->getBindings());
    }

    /**
     * @param int $length
     * @param null $pool
     * @return bool|string
     */
    public static function quickRandom($length = 16, $pool = null)
    {
        if (empty($pool)) {
            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    /**
     * @param $baseUrl
     * @param $endPoint
     * @param string $method
     * @param array $headers
     * @param array $params
     * @return mixed|null
     * @throws GuzzleException
     */
    public static function quickCurl($baseUrl, $endPoint, $method = 'get', $headers = [], array $params = [])
    {
        $defaultHeaders['Accept']       = 'application/json';
        $defaultHeaders['Content-Type'] = 'application/json';
        if ($headers) {
            $defaultHeaders = $headers;
        }
        $curl = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 60,
            'headers' => $defaultHeaders
        ]);
        /** @var ResponseInterface $result */
        $result = $curl->{$method}($endPoint . '?' . http_build_query($params));
        return json_decode($result->getBody()->getContents(), true);
    }

    /**
     * @param $baseUrl
     * @param array $endPoints
     * @param array $headers
     * @return array
     */
    public static function quickMultipleCurls($baseUrl, array $endPoints, $headers = [])
    {
        $defaultHeaders['Accept']       = 'application/json';
        $defaultHeaders['Content-Type'] = 'application/json';
        if ($headers) {
            $defaultHeaders = $headers;
        }
        $curl = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 60,
            'headers' => $defaultHeaders
        ]);

        $promises = [];
        foreach ($endPoints as $key => $endPoint) {
            $promises[$key] = $curl->getAsync($endPoint);
        }

        $responses = Utils::settle($promises)->wait();
        $results   = [];
        foreach ($responses as $key => $response) {
            if ($response['state'] == 'fulfilled') {
                /** @var ResponseInterface $responseValue */
                $responseValue = $response['value'];
                $results[$key] = json_decode($responseValue->getBody()->getContents(), true);
            }
        }

        return $results;
    }

    /**
     * @param Exception $exception
     * @return string
     */
    public static function getFullMessage(Exception $exception)
    {
        return $exception->getFile() . ' | ' . $exception->getLine() . ' | ' . $exception->getMessage();
    }

    /**
     * Sắp xếp theo key value của mảng nhiều chiều (vd name, value)
     * $pros = [
     * [
     * 'name' => 'b',
     * 'value' => 1
     * ],
     * [
     * 'name' => 'a',
     * 'value' => 1
     * ],
     * ];
     *
     * @param $key
     * @return Closure
     */
    public static function build_sorter($key)
    {
        return function ($a, $b) use ($key) {
            return strnatcmp($a[$key], $b[$key]);
        };
    }

    /**
     * @param string $base64File
     * @return UploadedFile
     */
    public static function createUploadedFilefromBase64(string $base64File): UploadedFile
    {
        $base64Image  = explode(";base64,", $base64File);
        $explodeImage = explode("image/", $base64Image[0]);
        $imageType    = $explodeImage[1];
        $image_base64 = base64_decode($base64Image[1]);
        $file         = '/tmp/' . uniqid() . '.' . $imageType;

        file_put_contents($file, $image_base64);

        $tempFileObject = new File($file);
        $uploadedFile   = new UploadedFile(
            $tempFileObject->getPathname(),
            $tempFileObject->getFilename(),
            $tempFileObject->getMimeType(),
            0,
            true // Mark it as test, since the file isn't from real HTTP POST.
        );

        // return UploadedFile object
        return $uploadedFile;
    }

}
