<?php

namespace Modules\Stock\Commands;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Stock\Events\StockChanged;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\User\Models\User;

class ChangeStock
{
    /**
     * @var Stock
     */
    protected $stock;
    /**
     * @var string
     */
    protected $action;
    /**
     * @var int
     */
    protected $quantity;
    /**
     * @var User
     */
    protected $creator;
    /**
     * @var StockObjectInterface
     */
    protected $object;
    /**
     * @var array
     */
    protected $payload;

    /**
     * ChangeStock constructor
     *
     * @param Stock $stock
     * @param string $action
     * @param int $quantity
     * @param User $creator
     */
    public function __construct(Stock $stock, string $action, int $quantity, User $creator)
    {
        $this->stock    = $stock;
        $this->action   = $action;
        $this->quantity = $quantity;
        $this->creator  = $creator;
    }

    /**
     * @param StockObjectInterface $object
     * @return static
     */
    public function for(StockObjectInterface $object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @param array $payload
     * @return static
     */
    public function with(array $payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @return StockLog
     */
    public function run()
    {
        return $this->handle();
    }

    /**
     * @return StockLog
     */
    public function handle()
    {
        $log = DB::transaction(function () {
            $data = $this->applyAction();
            return $this->log($data);
        });

        (new StockChanged($this->stock, $log))->queue();

        return $log;
    }

    /**
     * @return array
     */
    protected function applyAction()
    {
        switch ($this->action) {
            case Stock::ACTION_IMPORT:
            case Stock::ACTION_IMPORT_BY_RETURN:
            case Stock::ACTION_IMPORT_FOR_PICKING:
            case Stock::ACTION_IMPORT_FOR_CHANGE_POSITION:
            {
                $this->stock->update([
                    'real_quantity' => DB::raw('real_quantity + ' . $this->quantity),
                    'quantity' => DB::raw('quantity + ' . $this->quantity),
                ]);

                return [
                    'change' => StockLog::CHANGE_INCREASE
                ];
            }
            case Stock::ACTION_EXPORT:
            case Stock::ACTION_EXPORT_FOR_ORDER:
            case Stock::ACTION_EXPORT_FOR_PICKING:
            case Stock::ACTION_EXPORT_FOR_CHANGE_POSITION:
            {
                $this->stock->update([
                    'real_quantity' => DB::raw('real_quantity - ' . $this->quantity),
                    'quantity' => DB::raw('quantity - ' . $this->quantity),
                ]);

                return [
                    'change' => StockLog::CHANGE_DECREASE
                ];
            }
            default:
            {
                throw new InvalidArgumentException("The action $this->action invalid");
            }
        }
    }

    /**
     * @param array $data
     * @return StockLog|null
     */
    protected function log(array $data)
    {
        if (empty($this->object)) {
            throw new InvalidArgumentException("Object not found.");
        }

        $data = array_merge($data, [
            'tenant_id' => $this->stock->tenant_id,
            'sku_id' => $this->stock->sku_id,
            'action' => $this->action,
            'creator_id' => $this->creator->id,
            'real_quantity' => $this->quantity,
            'object_type' => $this->object->getObjectType(),
            'object_id' => $this->object->getObjectId(),
            'hash' => $this->stock->id . '|' . $this->action . '|' . $this->object->getObjectType() . '|' . $this->object->getObjectId()
        ]);

        if ($payload = $this->makePayload()) {
            $data['payload'] = $payload;
        }

        /** @var StockLog|null $log */
        $log = $this->stock->logs()->create($data);
        $log = StockLog::find($log->id); // query lại data để đảm bảo data đầy đủ và chính xác trước khi tạo sign
        $log->update(['sign' => $log->makeSign()]);

        return $log;
    }

    /**
     * @return array|null
     */
    protected function makePayload()
    {
        $payload = $this->payload;

        if ($this->object instanceof Model) {
            $payload = array_merge([
                strtolower($this->object->getObjectType()) => $this->object,
            ], $payload ?: []);
        }

        if (!$payload) {
            return null;
        }

        return array_map(function ($value) {
            if ($value instanceof Model) {
                return Arr::only($value->attributesToArray(), ['id', 'code', 'type']);
            }

            return $value;
        }, $payload);
    }
}
