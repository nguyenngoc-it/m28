<?php

namespace Modules\Merchant\Controllers;

use App\Base\Controller;
use Carbon\Carbon;
use Exception;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Auth\Services\Permission;
use Modules\Merchant\Commands\ChangeStateMerchant;
use Modules\Merchant\Commands\ConnectShopBase;
use Modules\Merchant\Commands\CreateMerchant;
use Modules\Merchant\Commands\CreateTransaction;
use Modules\Merchant\Commands\DisconnectShopBase;
use Modules\Merchant\Commands\UpdateMerchant;
use Modules\Merchant\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Modules\Merchant\Transformers\MerchantDetailTransformer;
use Modules\Merchant\Transformers\MerchantListItemTransformer;
use Modules\Merchant\Validators\ChangeStateMerchantValidator;
use Modules\Merchant\Validators\ConnectShopBaseValidator;
use Modules\Merchant\Validators\CreateMerchantValidator;
use Modules\Merchant\Validators\CreateTransactionValidator;
use Modules\Merchant\Validators\ListMerchantValidator;
use Modules\Merchant\Validators\UpdateMerchantValidator;
use Modules\Service;
use Modules\Transaction\Models\Transaction;

class AdminMerchantController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $user      = $this->getAuthUser();
        $merchants = $user->merchants()->where([
            'status' => true,
            'tenant_id' => $user->tenant_id
        ])->get();

        return $this->response()->success([
            'merchants' => $merchants->map(function (Merchant $merchant) {
                return (new MerchantListItemTransformer())->transform($merchant);
            })
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function suggest(): JsonResponse
    {
        $assigned  = $this->requests->get('assigned', false);
        $countryId = $this->requests->get('country_id');
        $requires  = $this->requests->get('requires', []);
        $merchants = Merchant::query()->where([
            'tenant_id' => $this->user->tenant_id
        ]);
        if ($assigned && !$this->user->can(Permission::PRODUCT_MANAGE_ALL)) {
            $merchants->whereIn('id', $this->user->merchants->pluck('id'));
        }
        if ($countryId) {
            $merchants->where('location_id', $countryId);
        }
        $resMerchants = in_array('service_pack', $requires) ?
            $merchants->chunkMap(function (Merchant $merchant) {
                return array_merge($merchant->attributesToArray(), ['service_pack' => $merchant->servicePack ? $merchant->servicePack->only('id', 'code', 'name') : []]);
            }, 50) : $merchants->chunkMap(function (Merchant $merchant) {
                return $merchant->attributesToArray();
            }, 50);

        return $this->response()->success(
            [
                'merchants' => $resMerchants
            ]
        );
    }

    /**
     * @return JsonResponse
     * @throws RestApiException
     */
    public function items()
    {
        $filers  = $this->getQueryFilter();
        $results = Service::merchant()->lists($filers);

        //m4 đang k cho lọc quá nhiều accounts
        $accountM4 = $this->user->tenant->m4Merchant()->getAccounts();
        $accounts  = $accountM4->getData();

        return $this->response()->success([
            'merchants' => array_map(function (Merchant $merchant) {
                return (new MerchantListItemTransformer())->transform($merchant);
            }, $results->items()),
            'pagination' => $results,
            'accounts' => $accounts
        ]);
    }


    /**
     * Tạo filter để query product
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(ListMerchantValidator::$keyRequests);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['tenant_id'] = $this->getAuthUser()->tenant_id;
        if (!isset($filter['status'])) {
            $filter['status'] = true;
        }

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

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function create()
    {
        $user  = $this->getAuthUser();
        $input = $this->request()->all();
        if (isset($input['free_days_of_storage']) && $input['free_days_of_storage'] === '') {
            $input['free_days_of_storage'] = null;
        }
        $validator = (new CreateMerchantValidator($user->tenant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $merchant = (new CreateMerchant($user, $input))->handle();
        if (!$merchant instanceof Merchant) {
            return $this->response()->error('INPUT_INVALID', ['error' => $merchant]);
        }

        return $this->response()->success(['merchant' => $merchant]);
    }

    /**
     * @param Merchant $merchant
     * @return JsonResponse
     */
    public function update(Merchant $merchant)
    {
        $user  = $this->getAuthUser();
        $input = $this->request()->all();
        if (isset($input['free_days_of_storage']) && $input['free_days_of_storage'] === '') {
            $input['free_days_of_storage'] = null;
        }
        $validator = (new UpdateMerchantValidator($merchant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $merchant = (new UpdateMerchant($merchant, $user, $input))->handle();
        $merchant = (new MerchantDetailTransformer())->transform($merchant);
        return $this->response()->success(compact('merchant'));
    }


    /**
     * @param Merchant $merchant
     * @return JsonResponse
     */
    public function changeState(Merchant $merchant)
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new ChangeStateMerchantValidator($merchant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $merchant = (new ChangeStateMerchant($merchant, $user, $input['status']))->handle();
        $merchant = (new MerchantDetailTransformer())->transform($merchant);
        return $this->response()->success($merchant);
    }

    /**
     * @param Merchant $merchant
     * @return JsonResponse
     */
    public function connectShopBase(Merchant $merchant)
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->only(Merchant::$shopBaseParams);
        $validator = (new ConnectShopBaseValidator($merchant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $merchant = (new ConnectShopBase($merchant, $user, $input))->handle();
        if (!$merchant) {
            return $this->response()->error('INPUT_INVALID', ['connect' => 'error']);
        }

        $merchant = (new MerchantDetailTransformer())->transform($merchant);
        return $this->response()->success($merchant);
    }

    /**
     * @param Merchant $merchant
     * @return JsonResponse
     */
    public function disConnectShopBase(Merchant $merchant)
    {
        $user = $this->getAuthUser();
        if (empty($merchant->shop_base_webhook_id)) {
            return $this->response()->error('INPUT_INVALID');
        }

        $merchant = (new DisconnectShopBase($merchant, $user))->handle();
        if (!$merchant) {
            return $this->response()->error('INPUT_INVALID', ['connect' => 'error']);
        }

        $merchant = (new MerchantDetailTransformer())->transform($merchant);
        return $this->response()->success($merchant);
    }

    /**
     * @param Merchant $merchant
     * @return JsonResponse
     */
    public function getSales(Merchant $merchant)
    {
        return $this->response()->success(['sales' => $merchant->sales]);
    }

    /**
     * @param string|null $date
     * @return string
     */
    protected function normalizeTimeEnd($date)
    {
        if (!$date) {
            return $date;
        }

        return Str::contains($date, ' ') ? $date : $date . ' 23:59:59';
    }

    /**
     * @param Merchant $merchant
     * @return JsonResponse
     * @throws RestApiException
     * @throws Exception
     */
    public function getTransactions(Merchant $merchant)
    {
        $filter = $this->request()->only(['types', 'query', 'created_at', 'page', 'per_page']);
        if (!empty($filter['created_at'])) {
            $filter['timestampFrom'] = (new Carbon($filter['created_at']['from']))->toIso8601ZuluString();
            $filter['timestampTo']   = (new Carbon($this->normalizeTimeEnd($filter['created_at']['to'])))->toIso8601ZuluString();
            unset($filter['created_at']);
        }

        if (!empty($filter['page'])) {
            $filter['page'] = $filter['page'] - 1;
        }
        $per_page       = Arr::get($filter, 'per_page', config('paginate.per_page'));
        $filter['size'] = $per_page;

        $tenant       = $this->user->tenant;
        $response     = $tenant->m4Merchant()->transactions($merchant->code, $filter);
        $transactions = $response->getData();

        return $this->response()->success([
            'transactions' => $transactions,
            'pagination' => [
                'current_page' => intval($response->getHeader('X-Page-Number')[0]) + 1,
                'page_total' => intval($response->getHeader('X-Page-Count')[0]),
                'per_page' => intval($response->getHeader('X-Page-Size')[0]),
                'total' => intval($response->getHeader('X-Total-Count')[0]),

            ],
            'currency' => $merchant->getCurrency(),
            'merchant' => $merchant
        ]);
    }

    /**
     * @param Merchant $merchant
     * @return JsonResponse
     */
    public function createTransaction(Merchant $merchant)
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->only(['type', 'amount', 'description']);
        $validator = (new CreateTransactionValidator($merchant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $transaction = (new CreateTransaction($merchant, $input, $user))->handle();
        if (!$transaction instanceof Transaction) {
            return $this->response()->error('INPUT_INVALID', ['error' => $transaction]);
        }

        return $this->response()->success([
            'merchant' => $merchant->only(['id', 'name', 'code', 'username']),
            'transaction' => $transaction->toArray()
        ]);
    }
}
