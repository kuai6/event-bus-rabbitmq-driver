<?php

namespace Kuai6\EventBus\RabbitMQ\Driver\Adapter;

use Kuai6\EventBus\Logger\LoggerAwareInterface;
use Kuai6\EventBus\Logger\LoggerAwareTrait;

/**
 * Class AbstractAdapter
 * @package Kuai6\EventBus\RabbitMQ\Driver\Adapter
 */
abstract class AbstractAdapter implements
    AdapterInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Настройки соеденения
     *
     * @var array|null
     */
    protected $connectionConfig;

    /**
     * @param $connection
     */
    public function __construct(array $connection = [])
    {
        $this->setConnectionConfig($connection);
    }

    /**
     * @return array
     */
    public function getConnectionConfig()
    {
        return $this->connectionConfig;
    }

    /**
     * @param array|null $connectionConfig
     *
     * @return $this
     */
    public function setConnectionConfig(array $connectionConfig = [])
    {
        $this->connectionConfig = $connectionConfig;

        return $this;
    }
}
