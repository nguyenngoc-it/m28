<?php

namespace App\Base;

use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Query\Builder;
use MongoDB\Collection;

/**
 * Class MongoModel
 *
 * @method static Builder|static query()
 * @method static static create(array $attributes = [])
 * @method static static|null find($id)
 * @method static static firstOrCreate(array $attributes, array $values = [])
 * @method static static firstOrNew(array $attributes, array $values = [])
 * @method static static updateOrCreate(array $attributes, array $values = [])
 */
class MongoModel extends Model
{
    protected $connection = 'mongodb';
    protected $guarded = ['_id'];

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    /**
     * @return Carbon|null
     */
    public function getCreatedAt()
    {
        return $this->getAttribute(static::CREATED_AT);
    }

    /**
     * @return Carbon|null
     */
    public function getUpdatedAt()
    {
        return $this->getAttribute(static::UPDATED_AT);
    }

    /**
     * @return Collection
     */
    public function getMongoCollection()
    {
        return $this->getConnection()->getMongoDB()->selectCollection($this->getTable());
    }

    /**
     * @return Collection
     */
    public static function queryMongo()
    {
        return (new static())->getMongoCollection();
    }
}
