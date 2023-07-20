<?php

namespace Modules\Tools\Controllers;

use App\Base\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\AggregateException;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Modules\Merchant\Models\Sale;
use Throwable;

class TestingController extends Controller
{
    public function checkDoubleRequest()
    {
        /** @var Sale $sale */
        $sale = Sale::query()->first();
        if (empty($sale)) {
            $newSeller = Sale::create([
                'tenant_id' => 1,
                'merchant_id' => 1,
                'username' => 'hellokitty'
            ]);
            return $this->response()->success(['seller' => $newSeller]);
        }
        return $this->response()->error('exists', ['existed_seller' => $sale]);
    }

    /**
     * @return JsonResponse
     * @throws Throwable
     */
    public function callMultiRequest()
    {
        $inputs    = $this->requests->only(['base_url', 'endpoint', 'count', 'headers', 'body']);
        $validator = Validator::make($inputs, [
            'base_url' => 'required|url',
            'endpoint' => 'required',
            'count' => 'int',
            'headers' => 'array',
            'body' => 'array'
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $client   = new Client([
            'base_uri' => $inputs['base_url'],
            'headers' => $inputs['headers'] ?? ['Content-Type' => 'application/json']
        ]);
        $method   = empty($inputs['body']) ? 'getAsync' : 'postAsync';
        $count    = Arr::get($inputs, 'count', 10);
        $promises = [];
        for ($i = 0; $i < $count; $i++) {
            if ($method == 'postAsync') {
                $promises[] = $client->postAsync($inputs['endpoint'], ['json' => $inputs['body']]);
            } else {
                $promises[] = $client->getAsync($inputs['endpoint']);
            }
        }

        try {
            $responses = Utils::some(
                2,
                $promises
            )->wait();
        } catch (AggregateException $aggregateException) {
            return $this->response()->success('Great!!! The double request was prevent...');
        }

        $views = [];
        /** @var Response $response */
        foreach ($responses as $response) {
            $views[]  = json_decode($response->getBody()->getContents(), true);
        }
        return $this->response()->success($views);
    }
}
