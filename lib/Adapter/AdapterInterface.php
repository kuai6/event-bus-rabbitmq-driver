<?php

namespace Kuai6\EventBus\RabbitMQ\Driver\Adapter;

use Kuai6\EventBus\MessageInterface;
use Kuai6\EventBus\RabbitMQ\Driver\Metadata;

/**
 * Interface AdapterInterface
 *
 * @package Kuai6\EventBus\Driver\RabbitMqDriver\Adapter
 */
interface AdapterInterface
{
    /**
     * Инициализация шины
     *
     * @param Metadata[] $metadata
     */
    public function initEventBus(array $metadata = []);


    /**
     * Настройки соеденения
     *
     * @return array
     */
    public function getConnectionConfig();


    /**
     * @param $eventName
     * @param MessageInterface $message
     * @param Metadata $metadata
     * @return
     */
    public function trigger($eventName, MessageInterface $message, Metadata $metadata);

    /**
     * @param Metadata $metadata
     * @param                   $callback
     *
     */
    public function attach(Metadata $metadata, callable $callback);

//    /**
//     * На основе данных пришедших из очереди, создает RawMessage
//     *
//     * @param array $rawData
//     *
//     * @return RawMessageInterface
//     */
//    public function buildRawMessage(array $rawData = []);
}
