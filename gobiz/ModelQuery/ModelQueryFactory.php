<?php

namespace Gobiz\ModelQuery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionObject;

abstract class ModelQueryFactory
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * Thông tin join đến các table liên quan
     *
     * VD: $joins = [
     *      'order_items' => 'order_id', // hasMany relationship có thể khai báo ngắn gọn như này
     *      'users' => ['orders.buyer_id', '=', 'users.id', 'inner'],
     * ]
     *
     * @var array
     */
    protected $joins = [];

    /**
     * Namespace của các filter (mặc định lấy theo namespace của đối tượng ModelQuery hiện tại)
     *
     * @var string
     */
    protected $filterNamespace;

    /**
     * Khởi tạo model
     */
    abstract protected function newModel();

    /**
     * Lấy đối tượng model
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model ?? $this->model = $this->newModel();
    }

    /**
     * Khởi tạo query mới theo filter
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        $query = $this->newModelQuery()
            ->select($this->model->qualifyColumn('*'));

        foreach ($filter as $param => $value) {
            $this->applyFilter($query, $param, $value);
        }

        return $query;
    }

    /**
     * @return ModelQuery
     */
    protected function newModelQuery()
    {
        return new ModelQuery($this->getModel()->newQuery(), $this->joins);
    }

    /**
     * @param ModelQuery $query
     * @param string $param
     * @param mixed $value
     */
    protected function applyFilter(ModelQuery $query, $param, $value)
    {
        if ($this->hasMethodFilter($param)) {
            $this->invokeMethodFilter($query, $param, $value);
            return;
        }

        if ($this->hasClassFilter($param)) {
            $this->invokeClassFilter($query, $param, $value);
            return;
        }

        $query->where($this->model->qualifyColumn($param), $value);
    }

    /**
     * @param string $param
     * @return bool
     */
    protected function hasMethodFilter($param)
    {
        return method_exists($this, $this->getMethodFilter($param));
    }

    /**
     * @param ModelQuery $query
     * @param string $param
     * @param mixed $value
     */
    protected function invokeMethodFilter(ModelQuery $query, $param, $value)
    {
        $this->{$this->getMethodFilter($param)}($query, $value);
    }

    /**
     * @param string $param
     * @return string
     */
    protected function getMethodFilter($param)
    {
        return 'apply' . Str::studly($param) . 'Filter';
    }

    /**
     * @param string $param
     * @return bool
     */
    protected function hasClassFilter($param)
    {
        return class_exists($this->getClassFilter($param));
    }

    /**
     * @param ModelQuery $query
     * @param string $param
     * @param mixed $value
     */
    protected function invokeClassFilter(ModelQuery $query, $param, $value)
    {
        $this->makeFilter($param)->apply($query, $value);
    }

    /**
     * @param string $param
     * @return FilterInterface
     */
    protected function makeFilter($param)
    {
        return app($this->getClassFilter($param));
    }

    /**
     * @param string $param
     * @return string
     */
    protected function getClassFilter($param)
    {
        return $this->getFilterNamespace() . '\\' . Str::studly($param) . 'Filter';
    }

    /**
     * @return string
     */
    protected function getFilterNamespace()
    {
        return $this->filterNamespace ?? (new ReflectionObject($this))->getNamespaceName();
    }

    /**
     * @param ModelQuery $query
     * @param string $column
     * @param array $range
     */
    protected function applyFilterTimeRange(ModelQuery $query, $column, array $range)
    {
        $from = Arr::get($range, 'from');
        $to   = ($to = Arr::get($range, 'to')) ? $this->normalizeTimeEnd($to) : $to;
        $this->applyFilterRange($query, $column, $from, $to);
    }

    /**
     * @param ModelQuery $query
     * @param string $column
     * @param mixed $from
     * @param mixed $to
     */
    protected function applyFilterRange(ModelQuery $query, $column, $from, $to)
    {
        if (!is_null($from)) {
            $query->where($column, '>=', $from);
        }
        if (!is_null($to)) {
            $query->where($column, '<=', $to);
        }
    }

    /**
     * @param string|null $date
     * @return string
     */
    protected function normalizeTimeEnd($date)
    {
        if (!$date) {
            return $date;
        }

        return Str::contains($date, ' ') ? $date : $date . ' 23:59:59';
    }
}
