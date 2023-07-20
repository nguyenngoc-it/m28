<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace Modules\Merchant\Controllers;

use App\Base\Controller;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use Exception;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Merchant\Commands\DownloadTransactions;
use Modules\Merchant\Commands\RegisterMerchant;
use Modules\Merchant\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Modules\Merchant\Validators\RegisterMerchantValidator;
use Modules\Service;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MerchantController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function register()
    {
        $input                         = $this->request()->all();
        $input['free_days_of_storage'] = Merchant::FREE_DAYS_OF_STORAGE;
        $validator                     = (new RegisterMerchantValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $tenant = $validator->getTenant();
        $user   = Service::user()->getSystemUserDefault();

        $merchant = (new RegisterMerchant($user, $validator->getLocation(), $input, $tenant))->handle();
        if (!$merchant instanceof Merchant) {
            return $this->response()->error('INPUT_INVALID', ['error' => $merchant]);
        }

        return $this->response()->success(['merchant' => $merchant]);
    }

    /**
     * @return JsonResponse
     * @throws RestApiException
     */
    public function balance()
    {
        $tenant   = $this->user->tenant;
        $account  = $tenant->m4Merchant()->getAccount($this->user->merchant->code)->getData();
        $currency = $this->user->merchant->getCurrency();
        $merchant = $this->user->merchant->only(['username', 'name', 'warning_out_money']);

        $accountBalance  = (double)data_get($account, 'balance', 0);
        $warningOutMoney = (double)data_get($merchant, 'warning_out_money', 0);

        $merchant['warning_out_money'] = [
            'balance' => $warningOutMoney
        ];
        if ($accountBalance < $warningOutMoney) {
            $merchant['warning_out_money']['status'] = true;
        } else {
            $merchant['warning_out_money']['status'] = false;
        }

        return $this->response()->success(compact('account', 'currency', 'merchant'));
    }


    /**
     * @param string|null $date
     * @return string
     */
    protected function normalizeTimeEnd(?string $date)
    {
        if (!$date) {
            return $date;
        }

        return Str::contains($date, ' ') ? $date : $date . ' 23:59:59';
    }

    /**
     * @return JsonResponse
     * @throws RestApiException
     * @throws Exception
     */
    public function transactions()
    {
        $filter = $this->requests->only(['types', 'query', 'created_at', 'page', 'per_page']);
        $filter = array_map(function ($data) {
            return is_string($data) ? trim($data) : $data;
        }, $filter);
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
        $response     = $tenant->m4Merchant()->transactions($this->user->merchant->code, $filter);
        $transactions = $response->getData();

        return $this->response()->success([
            'transactions' => $transactions,
            'pagination' => [
                'current_page' => intval($response->getHeader('X-Page-Number')[0]) + 1,
                'page_total' => intval($response->getHeader('X-Page-Count')[0]),
                'per_page' => intval($response->getHeader('X-Page-Size')[0]),
                'total' => intval($response->getHeader('X-Total-Count')[0]),
            ],
            'currency' => $this->user->merchant->getCurrency()
        ]);
    }

    /**
     * @return BinaryFileResponse
     * @throws RestApiException
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     * @throws Exception
     */
    public function downloadTransactions()
    {
        $filter = $this->requests->only(['types', 'query', 'created_at']);
        $filter = array_map(function ($data) {
            return is_string($data) ? trim($data) : $data;
        }, $filter);
        if (!empty($filter['created_at'])) {
            $filter['timestampFrom'] = (new Carbon($filter['created_at']['from']))->toIso8601ZuluString();
            $filter['timestampTo']   = (new Carbon($this->normalizeTimeEnd($filter['created_at']['to'])))->toIso8601ZuluString();
            unset($filter['created_at']);
        }
        $filter['size'] = 20000;
        $tenant         = $this->user->tenant;
        $response       = $tenant->m4Merchant()->transactions($this->user->merchant->code, $filter);
        $transactions   = $response->getData();

        $pathFile = (new DownloadTransactions($transactions))->handle();
        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * @return JsonResponse
     */
    public function getCountry()
    {
        return $this->response()->success($this->user->merchant->getCountry());
    }

    /**
     * @return JsonResponse
     */
    public function servicePack()
    {
        $servicePack       = $this->user->merchant->servicePack;
        $servicePackPrices = [];
        if ($servicePack) {
            /** @var Service\Models\ServicePackPrice $servicePackPrice */
            foreach ($servicePack->servicePackPrices as $servicePackPrice) {
                $servicePackPrices[$servicePackPrice->service->type]['type']                  = $servicePackPrice->service->type;
                $servicePackPrices[$servicePackPrice->service->type]['service_pack_prices'][] = [
                    'service_pack_price' => $servicePackPrice,
                    'service' => $servicePackPrice->service->only(['id', 'type', 'code', 'name', 'is_required']),
                    'service_price' => $servicePackPrice->servicePrice->only(['id', 'label', 'price', 'yield_price', 'deduct', 'note'])
                ];
            }
            if ($servicePackPrices) {
                $servicePackPrices = array_values($servicePackPrices);
            }
        }
        return $this->response()->success([
            'service_pack' => $servicePack,
            'service_pack_prices' => $servicePackPrices
        ]);
    }
}
