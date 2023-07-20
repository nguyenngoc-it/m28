<?php

namespace Modules\Category\Controllers;

use App\Base\Controller;
use Modules\Category\Transformers\CategoryListItemTransformer;
use Modules\Service;
use Modules\Category\Commands\CreateCategory;
use Modules\Category\Commands\UpdateCategory;
use Modules\Category\Models\Category;
use Modules\Category\Transformers\CategoryDetailTransformer;
use Modules\Category\Validators\CreateCategoryValidator;
use Modules\Category\Validators\ListCategoryValidator;
use Illuminate\Http\JsonResponse;
use Modules\Category\Validators\UpdateCategoryValidator;

class CategoryController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $filers = $this->getQueryFilter();
        $results = Service::category()->lists($filers);

        return $this->response()->success([
            'categories' => array_map(function ($category) {
                return (new CategoryListItemTransformer())->transform($category);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }


    /**
     * @param Category $category
     * @return JsonResponse
     */
    public function detail(Category $category)
    {
        $data = (new CategoryDetailTransformer())->transform($category);
        return $this->response()->success($data);
    }


    /**
     * Tạo filter để query product
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(ListCategoryValidator::$keyRequests);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['tenant_id'] = $this->getAuthUser()->tenant_id;

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
     * @throws \Exception
     */
    public function create()
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new CreateCategoryValidator($user->tenant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $category = (new CreateCategory($user, $input))->handle();

        return $this->response()->success(['category' => $category]);
    }

    /**
     * @param Category $category
     * @return JsonResponse
     */
    public function update(Category $category)
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new UpdateCategoryValidator($category, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $category = (new UpdateCategory($category, $user, $input))->handle();
        $category = (new CategoryDetailTransformer())->transform($category);
        return $this->response()->success($category);
    }
}
