## Command Bus

Thư viện xử lý đóng gói và mở rộng logic nghiệp vụ

#### Chức năng

- Đóng gói nghiệp vụ thành các command riêng
- Mở rộng logic nghiệp vụ thông qua các middleware

#### Cách dùng

```php
use App\Base\CommandBus;

/**
 * Define command thay đổi trạng thái mã vận đơn
 */
class ChangeFreightBillStatus extends CommandBus
{
    public $freightBill;
    public $status;
    public $creator;

    public function __construct(FreightBill $freightBill, $status, User $creator)
    {
        $this->freightBill = $freightBill;
        $this->status = $status;
        $this->creator = $creator;
    }

    public function handle()
    {
        $this->freightBill->update(['status' => $this->status]);
        return $this->freightBill;
    }
}

/**
 * Define middleware check quyền user khi user thay đổi trạng thái mã vận đơn
 */
class AuthorizeWhenChangeFreightBillStatus
{
    public function handle(ChangeFreightBillStatus $command, Closure $next)
    {
        if (!$command->creator->can('MANAGE_FREIGHT_BILL')) {
            throw new Exception("User can't change freight bill status");
        }
        
        return $next($command);
    }
}

/**
 * Middleware xử lý update trạng thái đơn khi trạng thái mã vận đơn thay đổi
 */
class UpdateOrderStatusFromFreightBill
{
    public function handle(ChangeFreightBillStatus $command, Closure $next)
    {
        $res = $next($command);
        Service::order()->updateStatusFromFreightBill($command->freightBill->order, $command->freightBill, $command->creator);

        return $res;
    }
}

/**
 * Khai báo middleware tương ứng với các command (file config/bus.php)
 */
return [
    'listen' => [
        ChangeFreightBillStatus::class => [
            AuthorizeWhenChangeFreightBillStatus::class,
            UpdateOrderStatusFromFreightBill::class,
        ],
    ],
];

/**
 * Khi dispatch command thì các middleware tương ứng của command sẽ được gọi
 */
(new ChangeFreightBillStatus($freightBill, 'DELIVERING', $creator))->dispatch();
```

## Model Query

Thư viện xử lý query data theo các đối tượng model

#### Chức năng

- Mặc định sẽ gán điều kiện = cho các key trong filter $query->where($key, $value)
- Tạo custom filter dạng method hoặc class riêng
- Có thể join đến 1 table nhiều lần trong các filter khác nhau, giúp các filter có thể hoàn toàn độc lập với nhau về logic

#### Cách dùng

```php
namespace Module\Order\QueryFilters;

class OrderQuery extends ModelQueryFactory
{
    /**
     * Thông tin join đến các table liên quan
     * $joins = [$table => [$first, $operator, $second, $type = 'inner'], ...]
     */
    protected $joins = [
        'order_items' => ['orders.id', '=', 'order_items.order_id'],
        'customers' => ['orders.customer_id', '=', 'customers.id'],
    ];

    /**
     * Namespace của các filter (mặc định lấy theo namespace của đối tượng ModelQuery hiện tại)
     */
//    protected $filterNamespace = '\Module\Order\QueryFilters';

    protected function newModel()
    {
        return new Order();
    }

    /**
     * Tạo custom filter cho param keyword. Method name theo format: apply<Param>Filter
     */
    protected function applyKeywordFilter(ModelQuery $query, $value)
    {
        $query->where('orders.code', 'LIKE', "%{$value}%");
    }

    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $from = Arr::get($input, 'from');
        $to = $this->normalizeTimeEnd(Arr::get($input, 'to'));
        $this->applyFilterRange($query, 'orders.created_at', $from, $to);
    }

    protected function applyCustomerFilter(ModelQuery $query, $value)
    {
        // Có thể join đến 1 table nhiều lần trong các filter
        $query->join('customers');
        $query->where('customers.id', $value);
    }
}

/**
 * Tạo custom filter cho param item. Classname theo format: <Param>Filter
 */
class ItemFilter implements FilterInterface
{
    public function apply(ModelQuery $query, $value)
    {
        $query->join('order_items');
        $query->where(function (Builder $query) use ($value) {
            $query->where('item_id', $value);
            $query->orWhere('item_code', $value);
        });
    }
}
```

## Workflow

Thư viện xử lý workflow cho các đối tượng

#### Chức năng

- Khai báo transitions đơn giản, ngắn gọn
- Hỗ trợ đảo ngược transition (chuyển ngược sang place trước đó)
- Hỗ trợ khai báo middleware khi update place để mở rộng logic khi có nhu cầu (VD middleware valite hoặc lưu log khi update place)

#### Cách dùng

1. Config workflow trong file config/workflow.php
2. Implement SubjectInterface cho đối tượng cần áp dụng workflow
3. Lấy đối tượng xử lý của 1 workflow ``WorkflowService::workflow($workflowName)``

```php
use Gobiz\Workflow\SubjectInterface;
use Gobiz\Workflow\WorkflowException;
use Gobiz\Workflow\ApplyTransitionCommand;
use Gobiz\Workflow\WorkflowMiddlewareInterface;

/**
 * Config workflow (file config/workflow.php)
 */
return [
    'workflows' => [
        'order' => [
            // Danh sách status
            'places' => [
                'DEPOSITED',
                'TRANSPORTING',
                'RECEIVED',
            ],

            // Khai báo status flow
            'transitions' => [
                'DEPOSITED' => [
                    'TRANSPORTING',
                    'RECEIVED',
                ],
                'TRANSPORTING' => [
                    'RECEIVED',
                ],
            ],

            // Cho phép chuyển ngược sang status trước đó hay không?
            'reverse_transitions' => false,

            // Khai báo các middleware khi chuyển status
            'middleware' => [
                ValidateOrderChangeStatus::class,
                LogOrderChangeStatus::class,
            ],
        ],
    ],
];

/**
 * Implements SubjectInterface cho đối tượng cần áp dụng workflow
 */
class Order implements SubjectInterface
{
    /**
     * Set subject's place
     *
     * @return string
     */
    public function getSubjectPlace()
    {
        return $this->getAttribute('status');
    }

    /**
     * Update current subject's place
     *
     * @param string $place
     * @throws WorkflowException
     */
    public function setSubjectPlace($place)
    {
        if (!$this->update(['status' => $place])) {
            throw new WorkflowException("Update status {$place} for order {$this->getKey()} failed");
        }
    }
}

/**
 * Middleware validate khi đổi trạng thái đơn
 */
class ValidateOrderChangeStatus implements WorkflowMiddlewareInterface
{
    public function handle(ApplyTransitionCommand $command, Closure $next)
    {
        $creator = $command->getPayload('creator');

        if (!$creator->can('UPDATE_ORDER')) {
            throw new WorkflowException("User can't update order status");
        }
        
        return $next($command);
    }
}

/**
 * Middlewaare log hành động thay đổi trạng thái đơn
 */
class LogOrderChangeStatus implements WorkflowMiddlewareInterface
{
    public function handle(ApplyTransitionCommand $command, Closure $next)
    {
        $order = $command->subject;
        $fromStatus = $order->status;
        $res = $next($command);

        $order->logActivity(OrderEvent::CHANGE_STATUS, $command->getPayload('creator'), [
            'order' => $order,
            'from_status' => $fromStatus,
            'to_status' => $order->status,
        ]);

        return $res;
    }
}

/**
 * Thay đổi trạng thái đơn thông qua workflow
 * Xem các methods của workflow trong WorkflowInterface
 */
WorkflowService::workflow('order')->change($order, $toStatus, ['creator' => $creator]));
```
