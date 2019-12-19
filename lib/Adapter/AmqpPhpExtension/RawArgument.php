<?php

namespace Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension;

use AMQPEnvelope;
use AMQPQueue;

/**
 * Class RawArgument
 * @package Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension
 */
class RawArgument implements RawArgumentInterface
{
    /**
     * @var AMQPEnvelope
     */
    protected $rawMessage;

    /**
     * @var AMQPQueue
     */
    protected $rawQueue;

    /**
     * @param AMQPEnvelope $rawMessage
     * @param AMQPQueue $rawQueue
     */
    public function __construct(AMQPEnvelope $rawMessage, AMQPQueue $rawQueue)
    {
        $this->rawMessage = $rawMessage;
        $this->rawQueue = $rawQueue;
    }

    /**
     * @param array $data
     *
     * @return RawArgument
     *
     * @throws \Kuai6\EventBus\RabbitMQ\Driver\Adapter\AmqpPhpExtension\Exception\InvalidRawArgumentException
     */
    public static function factory(array $data = [])
    {
        if (!array_key_exists(0, $data) || !$data[0] instanceof AMQPEnvelope) {
            $errMsg = 'Некорретнай формат аргументов. Первый элемент должен быть AMQPEnvelope';
            throw new Exception\InvalidRawArgumentException($errMsg);
        }
        if (!array_key_exists(1, $data) || !$data[1] instanceof AMQPQueue) {
            $errMsg = 'Некорретнай формат аргументов. Второй элемент должен быть AMQPQueue';
            throw new Exception\InvalidRawArgumentException($errMsg);
        }

        return new static($data[0], $data[1]);
    }

    /**
     * @return AMQPEnvelope
     */
    public function getRawMessage()
    {
        return $this->rawMessage;
    }

    /**
     * @return AMQPQueue
     */
    public function getRawQueue()
    {
        return $this->rawQueue;
    }
}
