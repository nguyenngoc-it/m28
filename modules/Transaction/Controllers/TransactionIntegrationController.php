<?php

namespace Modules\Transaction\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Merchant\Commands\CreateTransaction;
use Modules\Merchant\Models\Merchant;
use Modules\Tenant\Models\Tenant;
use Modules\Transaction\Models\Transaction;
use Gobiz\Log\LogService;
use Illuminate\Http\Request;
use Modules\Merchant\Validators\CreateTransactionIntergationValidator;

class TransactionIntegrationController extends Controller
{

    /**
     * @return \Psr\Log\LoggerInterface
     */
    protected function logger()
    {
        return LogService::logger('transaction-integration-api', [
            'input' => $this->request()->toArray()
        ]);
    }

    /**
     * @return JsonResponse
     * @throws \Gobiz\Support\RestApiException
     */
    public function getAccounts()
    {
        $tenant   = $this->getTenant();
        if(!$tenant instanceof Tenant) {
            return $tenant;
        }

        $filter   = $this->request()->all();
        $response = $tenant->m4Merchant()->getAccounts($filter);
        $header   = $response->getHeader();

        return $this->response()->success([
            'accounts' => $response->getData(),
            'pagination' => [
                'current_page' => (isset($header['X-Page-Number'][0])) ? intval($header['X-Page-Number'][0]) + 1 : 0,
                'page_total' => (isset($header['X-Page-Count'][0])) ? intval($header['X-Page-Count'][0]): 0,
                'per_page' => (isset($header['X-Page-Size'][0])) ? intval($header['X-Page-Size'][0]): 0,
                'total' =>  (isset($header['X-Total-Count'][0])) ? intval($header['X-Total-Count'][0]): 0,

            ],
        ]);
    }

    public function index(Request $request)
    {
       // Lấy chi tiết Transaction theo Order Id
        $requestData = $request->all();
        $orderId     = data_get($requestData, 'order_id');
        $transaction = Transaction::where('request.purchaseUnits', 'elemMatch', ['orderId' => $orderId])->get()->first();
        if ($transaction) {
            $transaction = $transaction->toArray();
        } else {
            $transaction = [];
        }
        return $this->response()->success([
            'transaction' => $transaction,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|JsonResponse|null
     */
    protected function getTenant()
    {
        $tenantCode   = $this->request()->header('X-Tenant');
        if(empty($tenantCode)) {
            $this->logger()->info('TENANT_REQUIRED');

            return $this->response()->error('TENANT_REQUIRED');
        }
        $tenant = Tenant::query()->firstWhere('code', trim($tenantCode));
        if(!$tenant instanceof Tenant) {
            $this->logger()->info('TENANT_INVALID', ['tenant' => $tenantCode]);

            return $this->response()->error('TENANT_INVALID', ['tenant' => $tenantCode]);
        }

        return $tenant;
    }


    /**
     * @param $account
     * @param $type
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|JsonResponse|null
     */
    protected function createTransaction($account, $type)
    {
        $input     = $this->request()->only(['amount', 'description', 'memo', 'source', 'teller', 'orderId']);
        $input['type'] = $type;

        $this->logger()->info('debug create transaction '.$account , $input);

        $tenant   = $this->getTenant();
        if(!$tenant instanceof Tenant) {
            return $tenant;
        }
        $merchant  = $tenant->merchants()->firstWhere('code', $account);
        if(!$merchant instanceof Merchant) {
            $this->logger()->info('ACCOUNT_INVALID', ['account' => $account]);

            return $this->response()->error('ACCOUNT_INVALID', ['account' => $account]);
        }

        $user      = $this->getAuthUser();
        $user->tenant_id = $tenant->id;

        $validator = (new CreateTransactionIntergationValidator($merchant, $input));
        if ($validator->fails()) {
            $this->logger()->info('VALIDATE_ERROR', ['errors' => $validator->getMessageBag()->toArray()]);
            
            $checkValidateDuplicateOrderId = data_get($validator->getMessageBag()->toArray(), 'orderId', null);
            if ($checkValidateDuplicateOrderId) {
                return $this->response()->error('DUPLICATE_ORDER_ID', $validator);
            }

            return $this->response()->error($validator);
        }

        $transaction = (new CreateTransaction($merchant, $input, $user))->handle();
        if(!$transaction instanceof Transaction) {
            $this->logger()->info('CREATE_TRANSACTION_ERROR', ['errors' => $transaction]);

            return $this->response()->error('INPUT_INVALID', ['error' => $transaction]);
        }

        $this->logger()->info('CREATE_TRANSACTION_SUCCESS', ['transaction' => $transaction->toArray()]);

        return $this->response()->success([
            'merchant' => $merchant->only(['id', 'name', 'code', 'username']),
            'transaction' => $transaction->toArray(),
        ]);
    }

    /**
     * @param string $account
     * @return JsonResponse
     */
    public function deposit($account)
    {
        return $this->createTransaction($account, Transaction::ACTION_DEPOSIT);
    }

    /**
     * @param string $account
     * @return JsonResponse
     */
    public function collect($account)
    {
        return $this->createTransaction($account, Transaction::ACTION_COLLECT);
    }
}
