<?php

namespace Modules\Category\Commands;

use Illuminate\Support\Arr;
use Modules\Category\Models\Category;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Category\Services\CategoryEvent;

class UpdateCategory
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
     * @var User
     */
    protected $category;

    /**
     * UpdateCategory constructor.
     * @param Category $category
     * @param User $creator
     * @param array $input
     */
    public function __construct(Category $category, User $creator, array $input)
    {
        $this->category = $category;
        $this->creator = $creator;
        $this->input = $input;
    }


    /**
     * @return Category
     */
    public function handle()
    {
        $this->category->update($this->input);

        $this->category->logActivity(CategoryEvent::UPDATE, $this->creator, $this->category->getChanges());

        return $this->category;
    }
}