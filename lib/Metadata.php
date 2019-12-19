<?php

namespace Kuai6\EventBus\RabbitMQ\Driver;

use Kuai6\EventBus\MetadataReader\MetadataInterface;

/**
 * Class Metadata
 * @package Kuai6\EventBus\RabbitMQ\Driver
 */
class Metadata implements MetadataInterface
{
    /**
     * var string
     */
    const EXCHANGE = 'exchange';
    /**
     * var string
     */
    const EXCHANGE_NAME = 'name';
    /**
     * var string
     */
    const EXCHANGE_TYPE = 'type';
    /**
     * var string
     */
    const EXCHANGE_DURABLE = 'durable';
    /**
     * var string
     */
    const QUEUE = 'queue';
    /**
     * var string
     */
    const QUEUE_NAME = 'name';
    /**
     * var string
     */
    const QUEUE_DURABLE = 'durable';
    /**
     * var string
     */
    const BINDING_KEY = 'bindingKey';
    /**
     * var string
     */
    const BINDING_KEY_NAME = 'name';

    /**
     * Queue name
     *
     * @var string
     */
    protected $queueName;

    /**
     * Exchange name
     *
     * @var string
     */
    protected $exchangeName;

    /**
     * Binding keys - keys to bind exchange and queue
     *
     * @var array
     */
    protected $bindingKeys = [];

    /**
     * Exchange type
     *
     * @var string
     */
    protected $exchangeType;

    /**
     * Exchange durable flag
     *
     * @var boolean|null
     */
    protected $flagExchangeDurable;


    /**
     * Queue durable flag
     *
     * @var boolean|null
     */
    protected $flagQueueDurable;

    /**
     * Metadata constructor.
     * @param $metadata
     * @throws \Kuai6\EventBus\RabbitMQ\Driver\Exception\InvalidMetadataException
     */
    public function __construct($metadata)
    {
        if (!is_array($metadata)) {
            throw new Exception\InvalidMetadataException(
                sprintf('Array expected, but %s given', gettype($metadata))
            );
        }
        if (array_key_exists(static::EXCHANGE, $metadata)) {
            if (!array_key_exists(static::EXCHANGE_NAME, $metadata[static::EXCHANGE])) {
                throw new Exception\InvalidMetadataException(
                    sprintf('Section %s not found', static::EXCHANGE_NAME)
                );
            }
            $this->setExchangeName($metadata[static::EXCHANGE][static::EXCHANGE_NAME]);

            if (!array_key_exists(static::EXCHANGE_TYPE, $metadata[static::EXCHANGE])) {
                throw new Exception\InvalidMetadataException(
                    sprintf('Section %s not found', static::EXCHANGE_TYPE)
                );
            }
            $this->setExchangeType($metadata[static::EXCHANGE][static::EXCHANGE_TYPE]);

            if (array_key_exists(static::EXCHANGE_DURABLE, $metadata[static::EXCHANGE])) {
                $this->setFlagExchangeDurable($metadata[static::EXCHANGE][static::EXCHANGE_DURABLE]);
            }
        }

        if (array_key_exists(static::QUEUE, $metadata)) {
            if (!array_key_exists(static::QUEUE_NAME, $metadata[static::QUEUE])) {
                throw new Exception\InvalidMetadataException(
                    sprintf('Section %s not found', static::QUEUE_NAME)
                );
            }
            $this->setQueueName($metadata[static::QUEUE][static::QUEUE_NAME]);

            if (array_key_exists(static::QUEUE_DURABLE, $metadata[static::QUEUE])) {
                $this->setFlagQueueDurable($metadata[static::QUEUE][static::QUEUE_DURABLE]);
            }
        }

        if (array_key_exists(static::BINDING_KEY, $metadata)) {
            $bindingKeysStorage = [];
            foreach ($metadata[static::BINDING_KEY] as $bindingKey) {
                if (!array_key_exists(static::BINDING_KEY_NAME, $bindingKey)) {
                    throw new Exception\InvalidMetadataException(
                        sprintf('Section %s not found', static::BINDING_KEY)
                    );
                }
                $bindingKeysStorage[$bindingKey[static::BINDING_KEY_NAME]] = $bindingKey[static::BINDING_KEY_NAME];
            }
            $this->setBindingKeys($bindingKeysStorage);
        }
    }

    /**
     * @return string|null
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * @param string $queueName
     * @return $this
     */
    public function setQueueName(string $queueName)
    {
        $this->queueName = $queueName;
        return $this;
    }

    /**
     * @return string
     */
    public function getExchangeName(): string
    {
        return $this->exchangeName;
    }

    /**
     * @param string $exchangeName
     * @return $this
     */
    public function setExchangeName(string $exchangeName)
    {
        $this->exchangeName = $exchangeName;
        return $this;
    }

    /**
     * @return array
     */
    public function getBindingKeys(): array
    {
        return $this->bindingKeys;
    }

    /**
     * @param array $bindingKeys
     * @return $this
     */
    public function setBindingKeys(array $bindingKeys)
    {
        $this->bindingKeys = $bindingKeys;
        return $this;
    }

    /**
     * @return string
     */
    public function getExchangeType(): string
    {
        return $this->exchangeType;
    }

    /**
     * @param string $exchangeType
     * @return $this
     */
    public function setExchangeType(string $exchangeType)
    {
        $this->exchangeType = $exchangeType;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getFlagExchangeDurable()
    {
        return $this->flagExchangeDurable;
    }

    /**
     * @param bool|null $flagExchangeDurable
     * @return $this
     */
    public function setFlagExchangeDurable($flagExchangeDurable)
    {
        $this->flagExchangeDurable = $flagExchangeDurable;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getFlagQueueDurable()
    {
        return $this->flagQueueDurable;
    }

    /**
     * @param bool|null $flagQueueDurable
     * @return $this
     */
    public function setFlagQueueDurable($flagQueueDurable)
    {
        $this->flagQueueDurable = $flagQueueDurable;
        return $this;
    }

    /**
     * Return metadata as string
     *
     * @return string
     */
    public function toString()
    {
        return '';
    }
}
