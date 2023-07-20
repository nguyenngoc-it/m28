<?php

namespace App\Base;

use Carbon\Carbon;
use Gobiz\Activity\Activity;
use Gobiz\Activity\ActivityService;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Str;
use Modules\User\Models\User;

/**
 * Class Model
 *
 * @method static static create(array $attributes = [])
 * @method static static updateOrCreate(array $attributes = [], array $update = [])
 * @method static static firstOrCreate(array $attributes1 = [], array $attributes2 = [])
 * @method static static|null find($id)
 */
abstract class Model extends BaseModel
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Log activity
     *
     * @param string $action
     * @param User $creator
     * @param array $payload
     * @param array $attributes
     * @return string
     */
    public function logActivity(string $action, User $creator, array $payload = [], array $attributes = []): string
    {
        if(empty($attributes['time'])){
            $attributes['time'] = Carbon::now();
        }
        $attributes = array_merge([
            'creator' => $creator,
            'action' => $action,
            'objects' => [Str::singular($this->getTable()) => $this->getKey()],
            'payload' => $payload,
        ], $attributes);

        return ActivityService::log(new Activity($attributes));
    }
}
