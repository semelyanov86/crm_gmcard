<?php

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Vtiger_AmpqHelper_Helper
{
    public const EXCHANGE_NOTIFICATIONS = 'contacts';
    public const QUEUE_NOTIFICATIONS = 'contacts';
    public const RECEIVE_NOTIFICATIONS = 'vtcontacts';
    public const INTERNAL_EVENTS = 'vtinternal';

    public static function initNotifications(AMQPChannel $channel): void
    {
        // queue_declare(name, passive, durable, exclusive, auto_delete)
        $channel->queue_declare(self::QUEUE_NOTIFICATIONS, false, true, false, false);
        // exchange_declare(name, type, passive, durable, auto_delete)
        $channel->exchange_declare(self::EXCHANGE_NOTIFICATIONS, 'fanout', false, true, false);
        $channel->queue_bind(self::QUEUE_NOTIFICATIONS, self::EXCHANGE_NOTIFICATIONS);
    }

    public static function initInternalEvents(AMQPChannel $channel): void
    {
        $channel->queue_declare(self::INTERNAL_EVENTS, false, true, false, false);
        $channel->exchange_declare(self::INTERNAL_EVENTS, 'fanout', false, true, false);
        $channel->queue_bind(self::INTERNAL_EVENTS, self::INTERNAL_EVENTS);
    }

    public static function registerShutdown(AMQPStreamConnection $connection, AMQPChannel $channel): void
    {
        register_shutdown_function(function (AMQPChannel $channel, AMQPStreamConnection $connection) {
            $channel->close();
            $connection->close();
        }, $channel, $connection);
    }

}