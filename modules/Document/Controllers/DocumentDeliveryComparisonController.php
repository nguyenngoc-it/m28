<?php

namespace Modules\Document\Controllers;

use App\Base\Controller;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Document\Commands\DownloadErrorComparison;
use Modules\Document\Models\Document;
use Modules\Document\Services\DocumentEvent;
use Modules\Document\Validators\CheckingDocumentDeliveryComparisonValidator;
use Modules\Location\Models\LocationShippingPartner;
use Modules\Service;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentDeliveryComparisonController extends Controller
{

    protected function transformDocumentDeliveryComparison(Document $document)
    {
        return [
            'document' => $document
        ];
    }

    /**
     * Kiểm tra thông tin hợp lệ trước khi tạo chứng từ
     *
     * @return JsonResponse
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function checking()
    {
        $inputs    = $this->requests->only([
            'shipping_partner_id',
            'file',
        ]);
        $validator = new CheckingDocumentDeliveryComparisonValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $result = Service::documentDeliveryComparison()->checking($validator->getShippingPartner(), $inputs['file'], $this->user);

        return $this->response()->success($result);
    }

    /**
     * @return JsonResponse
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function create()
    {
        $inputs    = $this->requests->only([
            'shipping_partner_id',
            'file',
        ]);
        $validator = new CheckingDocumentDeliveryComparisonValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $documentDeliveryComparison = Service::documentDeliveryComparison()->create($validator->getShippingPartner(), $inputs['file'], $this->user);
        return $this->response()->success(
            [
                'document' => $documentDeliveryComparison,
                'shipping_partner' => $documentDeliveryComparison->shippingPartner ? $documentDeliveryComparison->shippingPartner->only(['id', 'code', 'name']) : null,
                'document_delivery_comparisons' => $documentDeliveryComparison->documentDeliveryComparisons
            ]
        );
    }

    /**
     * @param Document $documentDeliveryComparison
     * @return JsonResponse
     */
    public function detail(Document $documentDeliveryComparison)
    {
        return $this->response()->success([
            'document' => $documentDeliveryComparison,
            'shipping_partner' => $documentDeliveryComparison->shippingPartner ?
                array_merge($documentDeliveryComparison->shippingPartner->only(['id', 'code', 'name']), ['currency' => $documentDeliveryComparison->shippingPartner->currency()]) : null,
            'document_delivery_comparisons' => $documentDeliveryComparison->documentDeliveryComparisons
        ]);
    }

    /**
     * @param Document $documentDeliveryComparison
     * @return JsonResponse
     */
    public function update(Document $documentDeliveryComparison)
    {
        $inputs = $this->request()->only(['note']);
        if (isset($inputs['note'])) {
            $documentDeliveryComparison->note = $inputs['note'];
            $documentDeliveryComparison->save();
            $documentDeliveryComparison->logActivity(DocumentEvent::UPDATE, $this->user, ['note' => $inputs['note']]);
        }
        return $this->response()->success([
            'document' => $documentDeliveryComparison,
            'shipping_partner' => $documentDeliveryComparison->shippingPartner ?
                array_merge($documentDeliveryComparison->shippingPartner->only(['id', 'code', 'name']), ['currency' => $documentDeliveryComparison->shippingPartner->currency()]) : null,
            'document_delivery_comparisons' => $documentDeliveryComparison->documentDeliveryComparisons
        ]);
    }

    /**
     * @param Document $documentDeliveryComparison
     * @return BinaryFileResponse
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws InvalidArgumentException
     * @throws WriterNotOpenedException
     */
    public function downloadErrorComparison(Document $documentDeliveryComparison)
    {
        $pathFile = (new DownloadErrorComparison($documentDeliveryComparison))->handle();

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $inputs = $this->request()->only([
            'code',
            'tracking_code',
            'creator_id',
            'shipping_partner_id',
            'created_at',
            'sort',
            'sortBy',
            'page',
            'per_page',
            'paginate'
        ]);
        $inputs = $this->makeFilterByShippingPartner($inputs);

        $result = Service::documentDeliveryComparison()->listing($inputs, $this->user->tenant_id);

        $response = [];
        if ($result instanceof LengthAwarePaginator) {
            $response['documents']  = collect($result->items())->map(function (Document $document) {
                return $this->transformDocumentDeliveryComparison($document);
            });
            $response['pagination'] = $result;
        } else {
            $response['documents'] = $result->map(function (Document $document) {
                return $this->transformDocumentDeliveryComparison($document);
            });
        }

        return $this->response()->success($response);
    }


    /**
     * @param $filter
     * @return mixed
     */
    protected function makeFilterByShippingPartner($filter)
    {
        $userLocationIds    = $this->user->locations->pluck('id')->toArray();
        $shippingPartnerIds = LocationShippingPartner::query()->whereIn('location_id', $userLocationIds)->pluck('shipping_partner_id')->toArray();
        if(!empty($filter['shipping_partner_id']) && in_array($filter['shipping_partner_id'], $shippingPartnerIds))
        {
            return $filter;
        }

        $filter['shipping_partner_id'] = $shippingPartnerIds;
        return $filter;
    }

}
