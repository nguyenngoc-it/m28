<?php

namespace Modules\Order\Controllers;

use App\Base\Controller;
use Exception;
use Modules\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class MerchantDropshipOrderController extends Controller
{

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function import()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user   = $this->getAuthUser();
        $path   = Service::order()->getRealPathFile($input['file']);
        $errors = Service::order()->merchantImportDropshipOrders($path, $user);

        return $this->response()->success(compact('errors'));
    }
}
