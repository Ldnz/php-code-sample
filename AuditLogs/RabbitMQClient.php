<?php

namespace AuditLogs;


use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;

/**
 * RabbitMQ Client for ***** Audit Logs Service
 *
 * See https://github.com/**************
 * to get more details about the queue, exchange settings
 * and the data structure being sent
 */
class RabbitMQClient implements ClientInterface
{
    private const DELIVERY_MODE_NON_PERSISTENT = 1;

    /**
     * @var \Enqueue\AmqpBunny\AmqpContext
     */
    private \Enqueue\AmqpBunny\AmqpContext $context;

    /**
     * @var AmqpTopic
     */
    private $topic;

    /**
     * @param array $config
     * @throws AuditLogsException
     */
    public function __construct(array $config)
    {
        $factory = (new AmqpConnectionFactory([
            'host' => $config['host'],
            'port' => $config['port'],
            'vhost' => $config['vhost'],
            'user' => $config['user'],
            'pass' => $config['pass'],
        ]));

        $this->context = $factory->createContext();

        $this->topic = $this->context->createTopic($config['exchange']);
        $this->topic->setType(AmqpTopic::TYPE_DIRECT);
        $this->topic->addFlag(AmqpTopic::FLAG_DURABLE);
        $this->context->declareTopic($this->topic);

        $queue = $this->context->createQueue($config['queueName']);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $queue->setArguments([
            'x-dead-letter-exchange' => 'failover-exchange',
            'x-dead-letter-routing-key' => 'failover-routingkey',
        ]);
        $this->context->declareQueue($queue);

        try {
            $this->context->bind(new AmqpBind($this->topic, $queue));
        } catch (\Throwable $exception) {
            throw new AuditLogsException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param array $data
     * @throws AuditLogsException
     */
    public function send(array $data): void
    {
        $message = $this->context->createMessage(json_encode($data));
        $message->setDeliveryMode(self::DELIVERY_MODE_NON_PERSISTENT);
        $message->setContentType('application/json');

        try {
            $this->context->createProducer()->send($this->topic, $message);
        } catch (\Throwable $exception) {
            throw new AuditLogsException($exception->getMessage(), $exception->getCode());
        }
    }
}
