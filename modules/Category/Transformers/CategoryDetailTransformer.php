<?php

namespace Modules\Category\Transformers;

use App\Base\Transformer;
use Modules\Category\Models\Category;
use Modules\Service;

class CategoryDetailTransformer extends Transformer
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
