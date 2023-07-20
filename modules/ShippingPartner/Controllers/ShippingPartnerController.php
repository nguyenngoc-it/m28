<?php /** @noinspection ALL */

namespace Modules\ShippingPartner\Controllers;

use App\Base\Controller;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Modules\Location\Models\Location;
use Modules\Service;
use Modules\ShippingPartner\Commands\DownloadExpectedTransportingTemplate;
use Modules\ShippingPartner\Models\ShippingPartner;
use Illuminate\Http\JsonResponse;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransportingPriceException;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiException;
use Modules\Warehouse\Models\Warehouse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class ShippingPartnerController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $user           = $this->getAuthUser();
        $locationId     = $this->request()->get('location_id');
        $locationCode   = $this->request()->get('location_code');
        $locationByUser = $this->request()->get('location_by_user');

        $status = $this->request()->get('status');
        if ($status === null) {
            $status = true;
        }

        $shippingPartners = [];
        $locationIds      = [];

        if ($locationByUser) {
            $locationIds = $this->user->locations->pluck('id')->toArray();
            if (empty($locationIds)) {
                return $this->response()->success(compact('shippingPartners'));
            }
        }

        if (!empty($locationId) || !empty($locationCode)) {
            $location = (!empty($locationId)) ? Location::find(intval($locationId))
                : Location::query()->firstWhere('code', trim($locationCode));
            if (!$location instanceof Location) {
                return $this->response()->success(compact('shippingPartners'));
            }

            if ($locationByUser && !in_array($location->id, $locationIds)) {
                return $this->response()->success(compact('shippingPartners'));
            }

            $locationIds = [$location->id];
        }

        if (!empty($locationIds)) {
            $query = ShippingPartner::query()
                ->select(['shipping_partners.*'])
                ->join('location_shipping_partners', 'location_shipping_partners.shipping_partner_id', 'shipping_partners.id')
                ->where('shipping_partners.tenant_id', $user->tenant_id)
                ->whereIn('location_shipping_partners.location_id', $locationIds);
            if (strtolower($status) != 'all') {
                $query->where('shipping_partners.status', $status);
            }

            $shippingPartners = $query->groupBy('shipping_partners.id')->get()->transform(function (ShippingPartner $shippingPartner) {
                return array_merge($shippingPartner->toArray(), ['country' => $shippingPartner->country()]);
            });

            return $this->response()->success(compact('shippingPartners'));
        }

        $query = ShippingPartner::query()->where('tenant_id', $user->tenant_id);
        if (strtolower($status) != 'all') {
            $query->where('status', $status);
        }

        $shippingPartners = $query->get()->transform(function (ShippingPartner $shippingPartner) {
            return array_merge($shippingPartner->toArray(), ['country' => $shippingPartner->country()]);
        });

        return $this->response()->success(compact('shippingPartners'));
    }

    /**
     * @param ShippingPartner $shippingPartner
     * @return JsonResponse
     * @throws RestApiException
     * @throws ShippingPartnerApiException
     */
    public function stampsUrl(ShippingPartner $shippingPartner)
    {
        try {
            return $this->response()->success([
                'url' => $shippingPartner->api()->getOrderStampsUrl($shippingPartner->id, $this->request()->get('freight_bill_codes')),
            ]);
        } catch (RestApiException $exception) {
            if ($code = $exception->getResponse()->getData('code')) {
                return $this->response()->error($code, $exception->getResponse()->getData('data'));
            }

            throw $exception;
        }
    }

    /**
     * @param ShippingPartner $shippingPartner
     * @return JsonResponse|BinaryFileResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function downloadExpectedTransportingTemplate(ShippingPartner $shippingPartner)
    {
        $warehouseId = Arr::get($this->request(), 'warehouse_id');
        /** @var Warehouse|null $warehouse */
        $warehouse = Warehouse::query()->where([
            'id' => $warehouseId,
            'country_id' => $shippingPartner->country()->id ?? 0
        ])->first();
        if (empty($warehouse)) {
            return $this->response()->error('INPUT_INVALID', ['warehouse' => 'exists']);
        }
        try {
            $pathFile = (new DownloadExpectedTransportingTemplate($shippingPartner, $warehouse))->handle();
            return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
        } catch (ExpectedTransportingPriceException $exception) {
            return $this->response()->error('INPUT_INVALID', ['message' => $exception->getMessage()]);
        }

    }

    /**
     * @param ShippingPartner $shippingPartner
     * @return JsonResponse
     */
    public function uploadExpectedTransportingPrice(ShippingPartner $shippingPartner)
    {
        $input       = $this->request()->only(['warehouse_id', 'file']);
        $validator   = Validator::make($input, [
            'warehouse_id' => 'required',
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);
        $warehouseId = Arr::get($input, 'warehouse_id');
        /** @var Warehouse|null $warehouse */
        $warehouse = Warehouse::query()->where([
            'id' => $warehouseId,
            'country_id' => $shippingPartner->country()->id ?? 0
        ])->first();
        if (empty($warehouse)) {
            return $this->response()->error('INPUT_INVALID', ['warehouse' => 'exists']);
        }
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        try {
            $errors = Service::shippingPartner()->uploadExpectedTransportingPrice(array_merge(['shipping_partner_id' => $shippingPartner->id], $input), $this->user);

            return $this->response()->success(compact('errors'));
        } catch (ExpectedTransportingPriceException $exception) {
            return $this->response()->error('INPUT_INVALID', ['message' => $exception->getMessage()]);
        }
    }
}
