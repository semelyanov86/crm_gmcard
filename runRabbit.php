<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
/*+*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * All Rights Reserved.
 */

/**
 * Start the cron services configured.
 */
include_once 'vtlib/Vtiger/Cron.php';
require_once 'modules/Emails/mail.php';

require_once 'vendor/autoload.php';
require_once 'config.php';
require_once 'config.inc.php';

if (file_exists('config.override.php')) {
    include_once "config.override.php";
}

require_once 'includes/Loader.php';
vimport('includes.runtime.EntryPoint');
include_once 'modules/Vtiger/helpers/AmpqHelper.php';

ini_set('display_errors', 'on'); version_compare(PHP_VERSION, '5.5') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

$site_URLArray = explode('/', $site_URL);

$version = explode('.', phpversion());

$php = ($version[0] * 10_000 + $version[1] * 100 + $version[2]);
if ($php < 50_300) {
    $hostName = php_uname('n');
} else {
    $hostName = gethostname();
}

function executeRabbitMq()
{
    global $rabbitData;
    global $log;
    global $current_user;

    echo '<comment>Consume message</comment>';
    try {
        $connection = new AMQPStreamConnection($rabbitData['host'], $rabbitData['port'], $rabbitData['user'], $rabbitData['password'], $rabbitData['vhost']);
    } catch (AMQPRuntimeException | \RuntimeException | \ErrorException $e) {
        $log->error($e->getMessage());
        echo $e->getMessage();
        die;
    }
    $channel = $connection->channel();
    Vtiger_AmpqHelper_Helper::initNotifications($channel);
    Vtiger_AmpqHelper_Helper::initInternalEvents($channel);
    Vtiger_AmpqHelper_Helper::registerShutdown($connection, $channel);

    $consumerTag = 'consumer_' . getmypid();
    $channel->basic_consume(Vtiger_AmpqHelper_Helper::RECEIVE_NOTIFICATIONS, $consumerTag, false, false, false, false, function ($message) {
        $messageData = json_decode($message->body, true);
        $module = $messageData['module'];
        if ($module) {
            $class = Vtiger_Loader::getComponentClassName('service', 'QueueProcessor', $module);
            $handle = new $class($messageData);
            $handle->handle();

            echo 'Message Received: wit ID: ' . $messageData['id'] . PHP_EOL;
        }

        $chanel = $message->delivery_info['channel'];
        $chanel->basic_ack($message->delivery_info['delivery_tag']);
    });

    while (count($channel->callbacks)) {
        $channel->wait();
    }
    echo '<info>Done</info>';
}

executeRabbitMq();

