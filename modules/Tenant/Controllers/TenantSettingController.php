<?php

namespace Modules\Tenant\Controllers;

use App\Base\Controller;
use Gobiz\Support\Helper;
use Modules\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantSetting;

class TenantSettingController extends Controller
{

    /**
     * @param $code
     * @return JsonResponse
     */
    public function getImages($code)
    {
        $code = trim($code);
        if (!$code) {
            return $this->response()->error('INPUT_INVALID', ['code' => 'required']);
        }

        if (strpos($code, ".")) {
            $tenant = Service::tenant()->findByDomain($code);
        } else {
            $tenant = Tenant::query()->where('code', $code)->first();
        }

        if (!$tenant) {
            return $this->response()->error('INPUT_INVALID', ['domain' => 'not_exist']);
        }

        return $this->response()->success([
            'login_image_url' => $tenant->getSetting(TenantSetting::LOGIN_IMAGE_URL),
            'register_image_url' => $tenant->getSetting(TenantSetting::REGISTER_IMAGE_URL)
        ]);
    }

    /**
     * @param Tenant $tenant
     * @param UploadedFile $image
     * @param $name
     * @return bool|string
     */
    protected function uploadBanner(Tenant $tenant, UploadedFile $image, $name)
    {
        $imagePath = 'images/' . $name . '_' . time() . '.jpg';
        $uploaded  = $tenant->storage()->put($imagePath, $image->openFile(), 'public');
        if (!$uploaded) {
            return false;
        }

        $imageUrl = $tenant->storage()->url($imagePath);
        $tenant->settings()->updateOrCreate([
            'key' => ($name == 'login') ? TenantSetting::LOGIN_IMAGE_URL : TenantSetting::REGISTER_IMAGE_URL
        ], [
            'value' => $imageUrl
        ]);

        return $imageUrl;
    }

    /**
     * @return JsonResponse
     * @throws \Exception
     */
    public function updateBanner()
    {
        $input     = $this->request()->only(['image_login', 'image_register']);
        $validator = Validator::make($input, [
            'image_login' => 'image|max:' . config('upload.max_size'),
            'image_register' => 'image|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        if (empty($input['image_login']) && empty($input['image_register'])) {
            return $this->response()->error('INPUT_INVALID', ['image' => 'required']);
        }

        $user   = $this->getAuthUser();
        $tenant = $user->tenant;

        $loginImageUrl    = '';
        $registerImageUrl = '';

        if (!empty($input['image_login'])) {
            $loginImageUrl = $this->uploadBanner($tenant, $input['image_login'], 'login');
            if (!$loginImageUrl) {
                $this->response()->error('INPUT_INVALID', ['image_login' => 'upload_error']);
            }
        }

        if (!empty($input['image_register'])) {
            $registerImageUrl = $this->uploadBanner($tenant, $input['image_register'], 'register');
            if (!$registerImageUrl) {
                $this->response()->error('INPUT_INVALID', ['image_register' => 'upload_error']);
            }
        }

        return $this->response()->success(compact('loginImageUrl', 'registerImageUrl'));
    }


    /** cấu hình bật tắt tự động tạo vận đơn
     * @return JsonResponse
     */
    public function settings()
    {
        $user                  = $this->getAuthUser();
        $tenant                = $user->tenant;
        $autoCreateFreightBill = $this->request()->only(['auto_create_freight_bill']);
        $tenant                = TenantSetting::updateOrCreate(
            [
                'key' => TenantSetting::AUTO_CREATE_FREIGHT_BILL,
                'tenant_id' => $tenant->id
            ],
            [
                'value' => $autoCreateFreightBill ? $autoCreateFreightBill['auto_create_freight_bill'] : false
            ]
        );
        return $this->response()->success($tenant);
    }

    /**
     * @return JsonResponse
     */
    public function getSetting()
    {
        $user   = $this->getAuthUser();
        $tenant = $user->tenant;

        return $this->response()->success([
            'auto_create_freight_bill' => $tenant->getSetting(TenantSetting::AUTO_CREATE_FREIGHT_BILL),
            'document_importing' => $tenant->getSetting(TenantSetting::DOCUMENT_IMPORTING)
        ]);
    }

    /** Cấu hình bật tắt nhập hàng theo tenant
     * @return JsonResponse
     */
    public function settingDocumentImporting(): JsonResponse
    {
        $user              = $this->getAuthUser();
        $tenant            = $user->tenant;
        $documentImporting = $this->request()->only(['document_importing']);
        $tenant            = TenantSetting::updateOrCreate(
            [
                'key' => TenantSetting::DOCUMENT_IMPORTING,
                'tenant_id' => $tenant->id
            ],
            [
                'value' => $documentImporting ? $documentImporting['document_importing'] : false
            ]
        );
        return $this->response()->success($tenant);
    }
}
