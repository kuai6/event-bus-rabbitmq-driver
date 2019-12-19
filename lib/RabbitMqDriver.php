<?php

namespace Kuai6\EventBus\RabbitMQ\Driver;

use Kuai6\EventBus\Driver\AbstractDriver;
use Kuai6\EventBus\Driver\MetadataAwareInterface;
use Kuai6\EventBus\Driver\MetadataAwareTrait;
use Kuai6\EventBus\Logger\LoggerAwareInterface;
use Kuai6\EventBus\Logger\LoggerAwareTrait;
use Kuai6\EventBus\MessageInterface;
use Kuai6\EventBus\RabbitMQ\Driver\Adapter\AdapterInterface;
use Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension\RawArgument;
use Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension\RawMessage;
use Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension\RawMessageInterface;

/**
 * Class RabbitMqDriver
 * @package Kuai6\EventBus\RabbitMQ\Driver
 */
class RabbitMqDriver extends AbstractDriver implements MetadataAwareInterface, LoggerAwareInterface
{
    use MetadataAwareTrait, LoggerAwareTrait;

    /**
     * Адаптер для работы с RabbitMq
     *
     * @var AdapterInterface
     */
    protected $adapter;

    private static $properties = [
        'correlation_id' => 'correlationId',
        'reply_to' => 'replyTo',
        'message_id' => 'messageId',
    ];

    /**
     * @return AdapterInterface
     * @throws \Kuai6\EventBus\RabbitMQ\Driver\Exception\InvalidAdapterNameException
     */
    public function getAdapter()
    {
        if ($this->adapter === null) {
            $builder = new AdapterBuilder();
            $this->adapter = $builder->build($this->getDriverConfig()->getAdapterName(), $this->getDriverConfig());
            if ($this->adapter instanceof LoggerAwareInterface) {
                if ($this->getLogger()) {
                    $this->adapter->setLogger($this->getLogger());
                }
            }
        }

        return $this->adapter;
    }

    /**
     * @param AdapterInterface $adapter
     * @return $this
     */
    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Инициализация шины
     *
     * @return void
     *
     * @throws \Kuai6\EventBus\RabbitMQ\Driver\Exception\InvalidAdapterNameException
     */
    public function init()
    {
        $this->getAdapter()->initEventBus($this->metadata);
    }

    /**
     * @param $eventName
     * @param MessageInterface $message
     *
     * @throws \Kuai6\EventBus\RabbitMQ\Driver\Exception\InvalidAdapterNameException
     */
    public function trigger($eventName, MessageInterface $message)
    {
        /** @var Metadata $metadata */
        $metadata = $this->metadata[get_class($message)];
        $this->getAdapter()->trigger($eventName, $message, $metadata);
    }

    /**
     * Подписывается на прием сообщений
     *
     * @param          $messageName
     * @param callable $callback
     *
     * @throws \Kuai6\EventBus\RabbitMQ\Driver\Exception\InvalidAdapterNameException
     * @throws \Kuai6\EventBus\Message\Exception\InvalidArgumentException
     */
    public function attach($messageName, callable $callback)
    {
        /** @var Metadata $metadata */
        $metadata = $this->metadata[$messageName];
        $this->getAdapter()->attach(
            $metadata,
            function (\AMQPEnvelope $envelope, \AMQPQueue $queue) use ($metadata, $messageName, $callback) {
                /** @var MessageInterface $message */
                $message = new $messageName();
                $message->getHeaders()->clearHeaders();
                foreach ($envelope->getHeaders() as $key => $value) {
                    $message->getHeaders()->addHeaderLine(sprintf('%s: %s', $key, $value));
                }
                foreach (self::$properties as $header => $property) {
                    $getter = 'get' . ucfirst($property);
                    if (method_exists($envelope, $getter) && $envelope->$getter()) {
                        $message->getHeaders()->addHeaderLine(
                            sprintf('%s: %s', $header, $envelope->$getter())
                        );
                    }
                }

                $message->setContent($envelope->getBody());
                $message->setRaw(new RawMessage(new RawArgument($envelope, $queue)));
                // exit from consume when return anything but null
                if (null !== call_user_func($callback, $message)) {
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * Confirm message
     *
     * @param MessageInterface $message
     * @return void
     */
    public function confirm(MessageInterface $message)
    {
        $raw = $message->getRaw();
        if ($raw instanceof RawMessageInterface) {
            $raw->confirm();
        }
    }

    /**
     * Reject message
     *
     * @param MessageInterface $message
     * @return void
     */
    public function reject(MessageInterface $message)
    {
        $raw = $message->getRaw();
        if ($raw instanceof RawMessageInterface) {
            $raw->reject();
        }
    }
}
