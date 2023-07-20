<?php

namespace Modules\Category\Commands;

use Illuminate\Support\Arr;
use Modules\Category\Models\Category;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Category\Models\CategoryArea;
use Modules\Category\Services\CategoryEvent;

class CreateCategory
{
    /**
     * @var array
     */
    protected $input;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var User
     */
    protected $creator;

    /**
     * CreateCategory constructor.
     * @param User $creator
     * @param array $input
     */
    public function __construct(User $creator, array $input)
    {
        $this->creator = $creator;
        $this->input = $input;
    }


    /**
     * @return Category
     */
    public function handle()
    {
        $tenant_id = $this->creator->tenant_id;
        $this->input['tenant_id'] = $tenant_id;
        $category = Category::create($this->input);

        $category->logActivity(CategoryEvent::CREATE, $this->creator);

        return $category;
    }
}