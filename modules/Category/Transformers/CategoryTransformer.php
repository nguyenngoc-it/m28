<?php

namespace Modules\Category\Transformers;

use App\Base\Transformer;
use Modules\Service;
use Modules\Category\Models\Category;

class CategoryTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Category $category
     * @return mixed
     */
    public function transform($category)
    {
        return array_merge($category->attributesToArray());
    }
}
