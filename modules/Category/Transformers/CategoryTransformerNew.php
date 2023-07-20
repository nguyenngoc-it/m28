<?php

namespace Modules\Category\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Category\Models\Category;

class CategoryTransformerNew extends TransformerAbstract
{

    public function transform(Category $category)
    {
        $dataReturn = [
            'id'   => $category->id,
            'code' => $category->code,
            'name' => $category->name,
        ];
       return $dataReturn;
    }

}
