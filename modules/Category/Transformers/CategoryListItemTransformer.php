<?php

namespace Modules\Category\Transformers;

use App\Base\Transformer;
use Modules\Category\Models\Category;

class CategoryListItemTransformer extends Transformer
{

    /**
     * Transform the data
     *
     * @param Category $category
     * @return mixed
     */
    public function transform($category)
    {
        return compact('category');
    }
}
