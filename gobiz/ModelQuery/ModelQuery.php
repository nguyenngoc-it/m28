<?php

namespace Gobiz\ModelQuery;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;

/**
 * Class ModelQuery
 *
 * @mixin Builder
 */
class ModelQuery
{
    use ForwardsCalls;

    /**
     * @var Builder
     */
    protected $query;

    /**
     * Thông tin join đến các table liên quan
     *
     * VD: $joins = [
     *      'order_items' => 'order_id', // HasMany relationship có thể khai báo ngắn gọn như này
     *      'users' => ['orders.buyer_id', '=', 'users.id', 'inner'],
     * ]
     *
     * @var array
     */
    protected $joins = [];

    /**
     * ModelQuery constructor
     *
     * @param Builder $query
     * @param array $joins
     */
    public function __construct(Builder $query, array $joins = [])
    {
        $this->query = $query;
        $this->joins = $joins;
    }

    /**
     * Thực hiện join đến table liên quan theo thông tin config
     *
     * @param string $table
     * @param Closure|null $join
     * @return static
     */
    public function join($table, Closure $join = null)
    {
        if ($this->isCurrentTable($table) || $this->joined($table)) {
            return $this;
        }

        if ($join) {
            $this->query->join($table, $join);
            return $this;
        }

        if ($config = $this->getJoinConfig($table)) {
            $this->query->join(...array_merge([$table], $config));
            return $this;
        }

        throw new InvalidArgumentException("Could not find join configuration for table {$table}");
    }

    /**
     * @param string $table
     * @return bool
     */
    protected function isCurrentTable($table)
    {
        return $table === $this->query->getModel()->getTable();
    }

    /**
     * @param string $table
     * @return array|null
     */
    protected function getJoinConfig($table)
    {
        if (!$config = Arr::get($this->joins, $table)) {
            return null;
        }

        return is_string($config)
            ? [$this->getModel()->getQualifiedKeyName(), '=', $table . '.' . $config]
            : $config;
    }

    /**
     * Kiểm tra đã join đến table này hay chưa
     *
     * @param string $table
     * @return bool
     */
    public function joined($table)
    {
        return !!(new Collection($this->query->getQuery()->joins))->firstWhere('table', $table);
    }

    /**
     * Lấy đối tượng eloquent query builder
     *
     * @return Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Lấy đối tượng eloquent model
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->query->getModel();
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $res = $this->forwardCallTo($this->query, $method, $parameters);

        return ($res instanceof Builder || $res instanceof \Illuminate\Database\Query\Builder) ? $this : $res;
    }
}
