<?php

namespace Kuai6\EventBus\RabbitMQ\Driver\Adapter;

use AMQPChannel;
use AMQPConnection;
use AMQPExchange;
use AMQPQueue;
use Kuai6\EventBus\Message\Header\CorrelationId;
use Kuai6\EventBus\Message\Header\MessageId;
use Kuai6\EventBus\Message\Header\ReplyTo;
use Kuai6\EventBus\Message\Header\Uuid;
use Kuai6\EventBus\MessageInterface;
use Kuai6\EventBus\RabbitMQ\Driver\Metadata;

/**
 * Class AmqpPhpExtension
 *
 * @package Kuai6\EventBus\Driver\RabbitMqDriver\Adapter
 */
class AmqpPhpExtension extends AbstractAdapter
{
    const HOST      = 'host';
    const PORT      = 'port';
    const LOGIN     = 'login';
    const PASSWORD  = 'password';
    const VHOST     = 'vhost';
    const PARAMS    = 'params';
    const READ_TIMEOUT      = 'read_timeout';
    const WRITE_TIMEOUT     = 'write_timeout';
    const CONNECT_TIMEOUT   = 'connect_timeout';
    const HEARTBEAT         = 'heartbeat';


    /**
     * Соответствие кодов обозначающих тип обменника, значению используемому для создания обменника
     *
     * @var array
     */
    protected static $exchangeTypeToCode = [
        'direct' => AMQP_EX_TYPE_DIRECT,
        'fanout' => AMQP_EX_TYPE_FANOUT,
        'header' => AMQP_EX_TYPE_HEADERS,
        'topic' => AMQP_EX_TYPE_TOPIC,
    ];

    /**
     * Соеденение с сервером RabbitMq
     *
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * Канал для взаимодействия с сервером RabbitMq
     *
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * Канал используемый для инициации шины(используются транзакции). После того как был использован функционал
     * работы с транзакиями требуется выполнять комиты в ручную, после таких действий как публикация сообщений.
     * Что бы избежать этого используютется два канала, один для инициации шины, второй для всех других действий
     *
     * @var AMQPChannel
     */
    protected $channelInitBus;

    /**
     * Имя расширения используемого для взаимодействия с сервером очередей
     *
     * @var string
     */
    protected static $amqpPhpExtensionName = 'amqp';

    /**
     * @param array $connection
     *
     * @throws Exception\AmqpPhpExtensionNotInstalledException
     */
    public function __construct(array $connection = [])
    {
        if (!extension_loaded(static::$amqpPhpExtensionName)) {
            $errMsg = sprintf('%s extension not found', static::$amqpPhpExtensionName);
            if ($this->getLogger()) {
                $this->getLogger()->emergency($errMsg);
            }
            throw new Exception\AmqpPhpExtensionNotInstalledException($errMsg);
        }
        parent::__construct($connection);
    }

    /**
     * Получение соеденения для работы с сервером очередей
     *
     * @return AMQPConnection
     */
    public function getConnection()
    {
        if ($this->connection) {
            return $this->connection;
        }

        $connectionConfig = $this->getConnectionConfig();
        $params = array_key_exists(static::PARAMS, $connectionConfig) ? $connectionConfig[static::PARAMS] : [];

        $this->connection = new AMQPConnection($params);

        return $this->connection;
    }

    /**
     * Получение и создание канала для работы с сервером очередей. Данный канал используется для всех действий кроме
     * инициации шины.
     *
     * @return AMQPChannel
     *
     * @throws \AMQPConnectionException
     */
    public function getChannel()
    {
        if ($this->channel) {
            return $this->channel;
        }

        $connection = $this->getConnection();
        if (!$connection->isConnected()) {
            $connection->connect();
        }
        $this->channel = new AMQPChannel($connection);

        return $this->channel;
    }


    /**
     * Получение канали используемого для инициации шины очередей
     *
     * @return AMQPChannel
     * @throws \AMQPConnectionException
     */
    public function getChannelInitBus()
    {
        if ($this->channelInitBus) {
            return $this->channelInitBus;
        }

        $connection = $this->getConnection();
        if (!$connection->isConnected()) {
            $connection->connect();
        }
        $this->channelInitBus = new AMQPChannel($connection);

        return $this->channelInitBus;
    }

    /**
     * Инициализация шины
     *
     * @param Metadata[] $metadata
     *
     * @throws Exception\InvalidMetadataException
     * @throws \Exception
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     */
    public function initEventBus(array $metadata = [])
    {
        $channel = $this->getChannelInitBus();
        try {
            $channel->startTransaction();
            foreach ($metadata as $data) {
                if (!$data instanceof Metadata) {
                    $errMsg = sprintf('Метаданные должны реализовывать класс %s', Metadata::class);
                    throw new Exception\InvalidMetadataException($errMsg);
                }
                if ($this->getLogger()) {
                    $this->getLogger()->info('Init event bus', ['data' => $data]);
                }
                $exchange = $this->createExchangeByMetadata($data, $channel);

                if ($data->getQueueName()) {
                    $queue = $this->createQueueByMetadata($data, $channel);

                    $bindKeys = $data->getBindingKeys();
                    foreach ($bindKeys as $bindKey) {
                        $queue->bind($exchange->getName(), $bindKey);
                    }
                }
            }

            $channel->commitTransaction();
        } catch (\Exception $e) {
            //@fixme Не работает корректно откат
            $channel->rollbackTransaction();
            if ($this->getLogger()) {
                $this->getLogger()->critical('Init event bus fail', ['exception' => $e]);
            }
            throw $e;
        }
    }

    /**
     * Создает обменник на основе метаданных
     *
     * @param Metadata $metadata
     * @param AMQPChannel $channel
     *
     * @return AMQPExchange
     *
     * @throws Exception\RuntimeException
     * @throws \AMQPExchangeException
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws Exception\InvalidExchangeTypeException
     */
    protected function createExchangeByMetadata(Metadata $metadata, AMQPChannel $channel)
    {
        $exchange = new AMQPExchange($channel);
        $exchange->setName($metadata->getExchangeName());
        $type = $this->getExchangeTypeByCode($metadata->getExchangeType());
        $exchange->setType($type);

        if (true === $metadata->getFlagExchangeDurable()) {
            $exchange->setFlags(AMQP_DURABLE);
        }

        $exchange->declareExchange();

        return $exchange;
    }

    /**
     * Создает очередь на основе метаданных
     *
     * @param Metadata $metadata
     * @param AMQPChannel $channel
     *
     * @return AMQPQueue
     *
     * @throws \AMQPQueueException
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     */
    protected function createQueueByMetadata(Metadata $metadata, AMQPChannel $channel)
    {
        $queue = new AMQPQueue($channel);

        $queue->setName($metadata->getQueueName());
        if (true === $metadata->getFlagQueueDurable()) {
            $queue->setFlags(AMQP_DURABLE);
        }

        $queue->declareQueue();

        return $queue;
    }

    /**
     * Получает значение типа обменника
     *
     * @param string $code
     *
     * @return string
     *
     * @throws Exception\InvalidExchangeTypeException
     */
    protected function getExchangeTypeByCode($code)
    {
        if (!array_key_exists($code, static::$exchangeTypeToCode)) {
            $errMsg = sprintf('Wrong Exchange type %s', $code);
            if ($this->getLogger()) {
                $this->getLogger()->critical($errMsg);
            }
            throw new Exception\InvalidExchangeTypeException($errMsg);
        }

        return static::$exchangeTypeToCode[$code];
    }


    /**
     * Публикация сообещния в очередь
     *
     * @param                   $eventName
     * @param MessageInterface $message
     * @param Metadata $metadata
     *
     * @throws \AMQPConnectionException
     *
     * @throws Exception\RuntimeException
     * @throws \AMQPExchangeException
     * @throws \AMQPChannelException
     * @throws Exception\InvalidExchangeTypeException
     *
     */
    public function trigger($eventName, MessageInterface $message, Metadata $metadata)
    {
        $channel = $this->getChannel();
        $exchange = $this->createExchangeByMetadata($metadata, $channel);

        $messageData = $message->getContent();

        $arguments = [
            'headers' => $message->getHeaders()->toArray(),
        ];

        $correlationId = $messageId = $message->getHeaders()->get(Uuid::NAME)->getFieldValue();
        if ($message->getHeaders()->has(CorrelationId::NAME)) {
            $correlationId = $message->getHeaders()->get(CorrelationId::NAME)->getFieldValue();
        }

        if ($message->getHeaders()->has(CorrelationId::NAME)) {
            $messageId = $message->getHeaders()->get(MessageId::NAME)->getFieldValue();
        }

        if ($message->getHeaders()->has(ReplyTo::NAME)) {
            $arguments['reply_to'] = $message->getHeaders()->get(ReplyTo::NAME)->getFieldValue();
        }

        $arguments['correlation_id'] = $correlationId;
        $arguments['message_id'] = $messageId;

        if ($this->getLogger()) {
            $this->getLogger()->info('Trigger message', ['data' => [
                'messageData' => $messageData, 'eventName' => $eventName, 'arguments' => $arguments
            ]]);
        }

        $result = $exchange->publish($messageData, $eventName, AMQP_NOPARAM, $arguments);

        if ($this->getLogger()) {
            if (!$result) {
                $this->getLogger()->error('Message trigger fail', ['data' => [
                    'messageData' => $messageData, 'eventName' => $eventName, 'arguments' => $arguments
                ]]);
            } else {
                $this->getLogger()->info('Message trigger successful', ['data' => [
                    'messageData' => $messageData, 'eventName' => $eventName, 'arguments' => $arguments
                ]]);
            }
        }
    }

    /**
     * @param Metadata $metadata
     * @param                   $callback
     *
     * @throws \AMQPExchangeException
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function attach(Metadata $metadata, callable $callback)
    {
        $channel = $this->getChannel();

        $queue = $this->createQueueByMetadata($metadata, $channel);
        $queue->consume($callback);
    }
}
