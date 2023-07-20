<?php

namespace Modules\Category\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Category\Models\Category;

class UpdateCategoryValidator extends Validator
{
    /**
     * UpdateCategoryValidator constructor.
     * @param Category $category
     * @param array $input
     */
    public function __construct(Category $category, array $input)
    {
        $this->category = $category;
        parent::__construct($input);
    }


    /**
     * @var Category
     */
    protected $category;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
        ];
    }
}