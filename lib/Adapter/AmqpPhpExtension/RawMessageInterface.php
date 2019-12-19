<?php

namespace Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension;

/**
 * Interface RawMessageInterface
 * @package Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension
 */
interface RawMessageInterface
{
    /**
     * Confirm message
     */
    public function confirm();

    /**
     * Reject message
     */
    public function reject();
}
