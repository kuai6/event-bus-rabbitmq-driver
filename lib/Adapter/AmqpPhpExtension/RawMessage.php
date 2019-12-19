<?php

namespace Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension;

/**
 * Class RawMessage
 * @package Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension
 */
class RawMessage implements RawMessageInterface
{
    /**
     * @var RawArgument
     */
    protected $rawArgument;

    /**
     * @param RawArgumentInterface $rawArgument
     */
    public function __construct(RawArgumentInterface $rawArgument)
    {
        $this->rawArgument = $rawArgument;
    }

    /**
     * Confirm message
     *
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     */
    public function confirm()
    {
        $this->rawArgument->getRawQueue()->ack($this->rawArgument->getRawMessage()->getDeliveryTag());
    }

    /**
     * Reject message
     *
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     */
    public function reject()
    {
        $this->rawArgument->getRawQueue()->nack($this->rawArgument->getRawMessage()->getDeliveryTag());
    }
}
