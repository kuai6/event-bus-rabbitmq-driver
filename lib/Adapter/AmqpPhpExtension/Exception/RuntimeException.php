<?php

namespace Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension\Exception;

use Kuai6\EventBus\RabbitMQ\Driver\Adapter\Exception\RuntimeException as Exception;

/**
 * Class RuntimeException
 * @package Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension\Exception
 */
class RuntimeException extends Exception implements
    ExceptionInterface
{
}
