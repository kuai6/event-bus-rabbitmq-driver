<?php

namespace Kuai6\EventBus\RabbitMQ\Driver;

use Kuai6\EventBus\Driver\DriverConfig;
use Kuai6\EventBus\RabbitMQ\Driver\Adapter\AdapterInterface;
use Kuai6\EventBus\RabbitMQ\Driver\Exception\InvalidAdapterNameException;

/**
 * Class AdapterBuilder
 * @package Kuai6\EventBus\RabbitMQ\Driver
 */
class AdapterBuilder
{
    /**
     * @param $name
     * @param DriverConfig $options
     * @return AdapterInterface
     * @throws \Kuai6\EventBus\RabbitMQ\Driver\Exception\InvalidAdapterNameException
     */
    public function build($name, DriverConfig $options)
    {
        if (!class_exists($name)) {
            throw new InvalidAdapterNameException(
                sprintf('Adapter class %s not exist', $name)
            );
        }

        $connectionConfig = $options->getConnectionConfig();
        if (array_key_exists('params', $connectionConfig)) {
            $connectionConfig = array_merge($connectionConfig, $connectionConfig['params']);
        }

        return new $name($connectionConfig);
    }
}
