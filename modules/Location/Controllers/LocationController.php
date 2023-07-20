<?php

namespace Modules\Location\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Location\Models\Location;
use Modules\Service;
use Modules\Location\Validators\ListLocationValidator;

class LocationController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filers    = $this->getQueryFilter();
        $validator = (new ListLocationValidator($filers));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $locations = Service::location()->lists($filers);

        return $this->response()->success(['locations' => $locations]);
    }

    /**
     * Lấy sách quốc gia đang được triển khai
     */
    public function active()
    {
        $locations = Service::location()->activeCountries();
        return $this->response()->success(['locations' => $locations]);
    }

    /**
     * Tạo filter để query product
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(ListLocationValidator::$keyRequests);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        if (
            $this->request()->get('created_at_from') &&
            $this->request()->get('created_at_to')
        ) {
            $filter['created_at'] = [
                'from' => $this->request()->get('created_at_from'),
                'to' => $this->request()->get('created_at_to'),
            ];
        }

        return $filter;
    }

}
