<?php

namespace Kuai6\EventBus\RabbitMQ\Driver\Adapter\Exception;

use Kuai6\EventBus\RabbitMQ\Driver\Exception\RuntimeException as Exception;

/**
 * Class RuntimeException
 * @package Kuai6\EventBus\RabbitMQ\Driver\Adapter\Exception
 */
class RuntimeException extends Exception implements
    ExceptionInterface
{
}
