<?php

namespace Modules\ActiveCode\Controllers;

use App\Base\Controller;
use Carbon\Carbon;
use Illuminate\Support\Str;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use Modules\ActiveCode\Models\ActiveCode;
use Modules\ActiveCode\Transformers\ActiveCodeTransformers;
use Modules\ActiveCode\Validators\ActiveCodeValidator;
use Modules\ActiveCode\Validators\ListActiveCodeValidator;
use Modules\Service;
use Modules\Stock\Transformers\StockTransformer;

class ActiveCodeController extends Controller
{


    /** Danh sách code active
     * @return \Illuminate\Http\JsonResponse
     */
    public function listCode($id)
    {
        $request        = $this->request()->all();
        $perPage        = data_get($request, 'per_page', 20);
        if ($perPage > 100) {
            $perPage = 100;
        }

        $paginator = ActiveCode::query()->select("active_codes.*")
            ->join('service_combos', 'active_codes.service_combo_id', '=', 'service_combos.id')
            ->where('service_combos.id','=', $id)
            ->orderBy('active_codes.id', 'DESC')
            ->paginate($perPage);
        $activeCode = $paginator->getCollection();
        $include   = data_get($request, 'include');
        $fractal   = new FractalManager();
        if ($include) {
            $fractal->parseIncludes($include);
        }
        $resource = new FractalCollection($activeCode, new ActiveCodeTransformers());
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $dataReturn = $fractal->createData($resource)->toArray();

        return $this->response()->success($dataReturn);

    }

    /** Tạo code của gói dịch vụ combo
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $inputs         = $this->request()->only([
            'service_combo_id',
            'type'
        ]);
        $serviceComboId = data_get($inputs, 'service_combo_id');
        $type           = data_get($inputs, 'type');
        $validator      = new ActiveCodeValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $expiredAt  = Carbon::now()->addDays(30);
        $code       = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
        $activeCode = ActiveCode::firstOrCreate(
            [
                'code' => $code,
                'service_combo_id' => $serviceComboId
            ],
            [
                'status' => ActiveCode::STATUS_NEW,
                'expired_at' => $expiredAt,
                'type' => $type
            ]
        );

        return $this->response()->success($activeCode);
    }
}
