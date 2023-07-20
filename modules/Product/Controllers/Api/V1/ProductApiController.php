<?php

namespace Modules\Product\Controllers\Api\V1;

use App\Base\ExternalController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Modules\Marketplace\Services\Marketplace;
use Modules\Product\Models\Sku;
use Modules\Product\Transformers\ProductTransformer;
use Modules\Product\Validators\MerchantNotUserCreateProductValidator;
use Modules\Product\Validators\MerchantNotUserUpdateProductValidator;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Warehouse\Models\Warehouse;

class ProductApiController extends ExternalController
{
    /**
     * Listing Products By Merchant
     *
     * @return JsonResponse
     */
    public function index()
    {
        $request = $this->request()->all();
        $perPage           = data_get($request, 'per_page');
        $merchantCode      = data_get($request, 'merchant_code');
        $productCode       = data_get($request, 'code');
        $warehouseCode     = data_get($request, 'warehouse_code');
        $status            = data_get($request, 'status');
        $productName       = data_get($request, 'name');
        $categoryId        = data_get($request, 'category_id');
        $lackOfExportGoods = data_get($request, 'lack_of_export_goods');
        $nearlySoldOut     = data_get($request, 'nearly_sold_out');
        $outOfStock        = data_get($request, 'out_of_stock');
        $notYetInStock     = data_get($request, 'not_yet_in_stock');

        $createdAt = [
            'from' => data_get($request, 'created_from'),
            'to'   => data_get($request, 'created_to'),
        ];

        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        $dataReturn = [];
        if ($merchant) {
            $creator = $merchant->user;
            $creatorId = 0;
            if ($creator) {
                $creatorId = $creator->id;
            }

            $warehouseId = null;
            if ($warehouseCode) {
                $warehouse = Warehouse::where('code', $warehouseCode)
                                        ->where('tenant_id', $merchant->tenant->id)
                                        ->first();
                if ($warehouse) {
                    $warehouseId = $warehouse->id;
                } else {
                    $warehouseId = 0;
                }
            }

            $paginator = Product::select('products.*')
                            //    ->ofCreator($creatorId)
                               ->ofMerchant($merchant->id)
                               ->warehouseId($warehouseId)
                               ->code($productCode)
                               ->status($status)
                               ->name($productName)
                               ->category($categoryId)
                               ->lackOfExportGoods($lackOfExportGoods)
                               ->nearlySoldOut($nearlySoldOut)
                               ->outOfStock($outOfStock)
                               ->notYetInStock($notYetInStock)
                               ->createdAt($createdAt)
                               ->orderBy('products.id', 'DESC')
                               ->paginate($perPage);
            
            $products = $paginator->getCollection();

            $include = data_get($request, 'include');
            $fractal  = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalCollection($products, new ProductTransformer);
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

            $dataReturn = $fractal->createData($resource)->toArray();
        } 
        return $this->response()->success($dataReturn);
        
    }

    /**
     * Get Product Detail
     *
     * @param int $productId
     * @return JsonResponse
     */
    public function detail($productId)
    {
        $request = $this->request()->all();

        $merchantCode = data_get($request, 'merchant_code');

        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        $dataReturn = [];
        if ($merchant) {
            $creator = $merchant->user;
            $creatorId = 0;
            if ($creator) {
                $creatorId = $creator->id;
            }

            $product = Product::select('products.*')
                            // ->ofCreator($creatorId)
                            ->ofMerchant($merchant->id)
                            ->where('id', $productId)
                            ->first();

            $include = data_get($request, 'include');
            $fractal  = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalItem($product, new ProductTransformer);

            $dataReturn = $fractal->createData($resource)->toArray();
        }

        return $this->response()->success($dataReturn);
    }

    /**
     * Create Product
     *
     * @return JsonResponse
     */
    public function create()
    {
        $request = $this->request()->all();

        $merchantCode = data_get($request, 'merchant_code');
        $name         = data_get($request, 'name');
        $categoryId   = data_get($request, 'category_id');
        $code         = data_get($request, 'code');
        $description  = data_get($request, 'description');
        $price        = data_get($request, 'price');
        $weight       = data_get($request, 'weight');
        $height       = data_get($request, 'height');
        $width        = data_get($request, 'width');
        $length       = data_get($request, 'length');

        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        $dataReturn = [];
        if ($merchant) {
            $store = Store::where('marketplace_code', Marketplace::CODE_VELAONE)->first();
            // Get next product
            // $productLastest = Product::orderBy('id', 'DESC')->first();
            // $skuLastest     = Sku::orderBy('id', 'DESC')->first();

            if (!$code) {
                $code = 'VLP_' . time();
            }

            $data3rdResource = [
                'name'              => $name,
                'category_id'       => $categoryId,
                'price'             => $price,
                'original_price'    => $price,
                'code'              => $code,
                'source'            => $store->marketplace_code,
                'product_id_origin' => $code,
                'sku_id_origin'     => $code,
                'creator_id'        => $merchant->user ? $merchant->user->id : Service::user()->getSystemUserDefault()->id,
                'merchant_id'       => $merchant->id,
                'description'       => $description,
                'weight'            => $weight,
                'height'            => $height,
                'width'             => $width,
                'length'            => $length,
                "status"            => Product::STATUS_ON_SELL
            ];
    
            // dd($data3rdResource);

            $validator = new MerchantNotUserCreateProductValidator($data3rdResource);

            if ($validator->fails()) {
                return $this->response()->error($validator);
            }
    
            $product = Service::product()->createProductFrom3rdPartner($store, $data3rdResource);
            
            $fractal  = new FractalManager();
            $resource = new FractalItem($product, new ProductTransformer);

            $dataReturn = $fractal->createData($resource)->toArray();
        }

        return $this->response()->success($dataReturn);
    }

    /**
     * Update Product
     *
     * @return JsonResponse
     */
    public function update($productId)
    {
        $request = $this->request()->all();

        $merchantCode = data_get($request, 'merchant_code');

        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        $dataReturn = [];
        if ($merchant) {
            $creator = $merchant->user;
            $creatorId = 0;
            if ($creator) {
                $creatorId = $creator->id;
            }

            $product = Product::select('products.*')
                            // ->ofCreator($creatorId)
                            ->ofMerchant($merchant->id)
                            ->where('id', $productId)
                            ->first();


            if ($product) {
                $name         = data_get($request, 'name', $product->name);
                $code         = data_get($request, 'code', $product->code);
                $categoryId   = data_get($request, 'category_id', $product->category_id);
                $description  = data_get($request, 'description', $product->description);
                $price        = data_get($request, 'price', $product->price);
                $weight       = data_get($request, 'weight', $product->weight);
                $height       = data_get($request, 'height', $product->height);
                $width        = data_get($request, 'width', $product->width);
                $length       = data_get($request, 'length', $product->length);
                $status       = data_get($request, 'status', $product->status);

                $store = Store::where('marketplace_code', Marketplace::CODE_VELAONE)->first();

                $data3rdResource = [
                    'name'              => $name,
                    'category_id'       => $categoryId,
                    'price'             => $price,
                    'original_price'    => $price,
                    'code'              => $code,
                    'source'            => $product->source,
                    'product_id_origin' => $product->product_id_origin,
                    'sku_id_origin'     => $product->sku_id_origin,
                    'creator_id'        => $product->creator_id,
                    'merchant_id'       => $product->merchant_id,
                    'images'            => $product->images,
                    'description'       => $description,
                    'weight'            => $weight,
                    'height'            => $height,
                    'width'             => $width,
                    'length'            => $length,
                    "status"            => $status
                ];

                $validator = new MerchantNotUserUpdateProductValidator($data3rdResource);

                if ($validator->fails()) {
                    return $this->response()->error($validator);
                }
        
                $productUpdated = Service::product()->createProductFrom3rdPartner($store, $data3rdResource);
                
                $fractal  = new FractalManager();
                $resource = new FractalItem($productUpdated, new ProductTransformer);

                $dataReturn = $fractal->createData($resource)->toArray();
            }
        }

        return $this->response()->success($dataReturn);
    }

    /**
     * Create Product
     *
     * @return JsonResponse
     */
    public function uploadImges($productId)
    {
        $request = $this->request()->all();
        $files = data_get($request, 'files');

        $merchantCode = data_get($request, 'merchant_code');

        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        $dataReturn = [];
        if ($merchant) {
            $creator = $merchant->user;
            $creatorId = 0;
            if ($creator) {
                $creatorId = $creator->id;
            }

            $product = Product::select('products.*')
                            // ->ofCreator($creatorId)
                            ->ofMerchant($merchant->id)
                            ->where('id', $productId)
                            ->first();


            if ($product) {
                $images     = $product->images;
                $uploadUrls = [];
                if ($files) {
                    foreach ($files as $file) {
                        $nameFile = md5($file->getClientOriginalName());
                        $filePath = 'products/' . $product->code . '/' . $nameFile . '.' . $file->extension();
                        $fileData = file_get_contents($file->getRealPath());

                        if (App::environment('local')) {
                            if (Storage::put($filePath, $fileData)) {
                                $uploadUrls[] = Storage::url($filePath);
                                unlink($file->getRealPath());
                            }
                        } else {
                            $uploaded = $product->tenant->storage()->put($filePath, $file->openFile(), 'public');
                            if ($uploaded) {
                                $uploadedUrl  = $product->tenant->storage()->url($filePath);
                                $uploadUrls[] = $uploadedUrl;
                                unlink($file->getRealPath());
                            }  
                        }
                    }
                }
                if ($uploadUrls) {
                    $images = array_unique(array_merge((array)$images, $uploadUrls));
                }
                $product->images = $images;
                $product->save();
                $fractal  = new FractalManager();
                $resource = new FractalItem($product, new ProductTransformer);

                $dataReturn = $fractal->createData($resource)->toArray();
            }
        }

        return $this->response()->success($dataReturn);
    }
}