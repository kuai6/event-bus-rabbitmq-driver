<?php

namespace Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension;

use AMQPEnvelope;
use AMQPQueue;

/**
 * Interface RawArgumentInterface
 * @package Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension
 */
interface RawArgumentInterface
{
    /**
     * @param array $data
     *
     * @return RawArgument
     *
     */
    public static function factory(array $data = []);

    /**
     * @return AMQPEnvelope
     */
    public function getRawMessage();

    /**
     * @return AMQPQueue
     */
    public function getRawQueue();
}
