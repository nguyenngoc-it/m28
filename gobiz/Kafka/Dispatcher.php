<?php

namespace Gobiz\Kafka;

use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\Producer;
use RdKafka\TopicConf;
use RuntimeException;

class Dispatcher implements DispatcherInterface
{
    /**
     * @var string
     */
    protected $brokers;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    public $consumerGroupPrefix = '';

    /**
     * @var bool
     */
    public $debug;

    /**
     * @var Producer
     */
    protected $producer;

    /**
     * Dispatcher constructor
     *
     * @param string $brokers
     * @param LoggerInterface $logger
     */
    public function __construct($brokers, LoggerInterface $logger)
    {
        $this->brokers = $brokers;
        $this->logger = $logger;
    }

    /**
     * Publish message to the given topic
     *
     * @param string $topic
     * @param string $payload
     * @param null|string $key
     */
    public function publish($topic, $payload, $key = null)
    {
        $conf = new TopicConf();
        $conf->set('message.timeout.ms', 1000);

        $this->makeProducer()
            ->newTopic($topic, $conf)
            ->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $key);

        $this->logDebug('publish', compact('topic', 'key', 'payload'));
    }

    /**
     * @return Producer
     */
    protected function makeProducer()
    {
        if (is_null($this->producer)) {
            $this->producer = new Producer();
            $this->producer->addBrokers($this->brokers);
        }

        return $this->producer;
    }

    /**
     * Subscribe message of the given topics
     *
     * @param string|array $topics
     * @param string $groupId
     * @param callable $listener
     */
    public function subscribe($topics, $groupId, callable $listener)
    {
        $groupId = $this->consumerGroupPrefix . $groupId;

        $consumer = $this->makeConsumer($groupId);

        $consumer->subscribe((array)$topics);

        while (true) {
            $message = $consumer->consume(120 * 1000);
            $this->processConsumedMessage($message, $listener);
        }
    }

    /**
     * @param string $groupId
     * @return KafkaConsumer
     */
    protected function makeConsumer($groupId)
    {
        $conf = new Conf();

        // Configure the group.id. All consumer with the same group.id will consume different partitions.
        $conf->set('group.id', $groupId);

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', $this->brokers);

        // Set a rebalance callback to log partition assignments (optional)
        $conf->setRebalanceCb(function (KafkaConsumer $kafka, $error, array $partitions = null) use ($conf) {
            $this->rebalancePartitions($conf, $kafka, $error, $partitions);
        });

        // Handle error
        $conf->setErrorCb(function ($kafka, $code, $message) {
            $this->logger->error($code . ': ' . $message);
            throw new RuntimeException($message, $code);
        });

        $topicConf = new TopicConf();

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
//        $topicConf->set('auto.offset.reset', 'smallest');

        // Set the configuration to use for subscribed/assigned topics
        $conf->setDefaultTopicConf($topicConf);

        return new KafkaConsumer($conf);
    }

    /**
     * @param Conf $conf
     * @param KafkaConsumer $kafka
     * @param int $error
     * @param array|null $partitions
     */
    public function rebalancePartitions($conf, KafkaConsumer $kafka, $error, array $partitions = null)
    {
        $logContext = Arr::only($conf->dump(), ['group.id']);

        switch ($error) {
            case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                $kafka->assign($partitions);
                $this->logDebug('assign_partition', $logContext);
                return;

            case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                $kafka->assign(null);
                $this->logDebug('revoke_partitions', $logContext);
                return;

            default:
                $this->logger->error('rebalance_error: ' . $error, $logContext);
                throw new RuntimeException($error);
        }
    }

    /**
     * @param Message $message
     * @param callable $listener
     */
    protected function processConsumedMessage($message, callable $listener)
    {
        if (!isset($message->err)) {
            $this->logger->error('Message error not isset');
        }

        switch ($message->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                $listener($message);
                $this->logConsumedMessage($message);
                return;

            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                $this->logDebug('No more messages, will wait for more');
                return;

            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                $this->logDebug('Timed out');
                break;

            default:
                $this->logger->error($message->errstr());
                throw new RuntimeException($message->errstr(), $message->err);
                break;
        }
    }

    /**
     * @param Message $message
     */
    protected function logConsumedMessage($message)
    {
        $this->logDebug('subscribe', [
            'topic' => $message->topic_name,
            'key' => $message->key,
            'offset' => $message->offset,
            'payload' => $message->payload,
            'error' => $message->err,
            'partition' => $message->partition,
        ]);
    }

    /**
     * @param string $message
     * @param array $context
     */
    protected function logDebug($message, array $context = [])
    {
        if ($this->debug) {
            $this->logger->debug($message, $context);
        }
    }
}