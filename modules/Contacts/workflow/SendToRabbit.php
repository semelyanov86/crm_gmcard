<?php

require_once 'modules/Vtiger/helpers/AmpqHelper.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPRuntimeException;

function SendToRabbit($ws_entity)
{
    global $rabbitData;
    global $log;

    $ws_id = $ws_entity->getId();
    $module = $ws_entity->getModuleName();
    if (empty($ws_id) || empty($module)) {
        return;
    }

    $crmid = vtws_getCRMEntityId($ws_id);
    if ($crmid <= 0 ) {
        return;
    }

    /** @var Contacts_Record_Model $myModuleInstance */
    $myModuleInstance = Vtiger_Record_Model::getInstanceById($crmid);

    try {
        $host = $rabbitData['host'];
        $port = (int) $rabbitData['port'];
        $user = $rabbitData['user'];
        $pass = $rabbitData['password'];
        $vhost = $rabbitData['vhost'];

        $connectionOptions = [
            'heartbeat' => 60,
            'connection_timeout' => 5.0,
            'read_write_timeout' => 5.0,
        ];

        if ($port === 5671) {
            $sslOptions = [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ];
            $connection = new AMQPSSLConnection($host, $port, $user, $pass, $vhost, $sslOptions, $connectionOptions);
        } else {
            $connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost, false, 'AMQPLAIN', null, 'en_US', $connectionOptions['heartbeat'], $connectionOptions['connection_timeout'], null, false, $connectionOptions['read_write_timeout']);
        }
    } catch (AMQPRuntimeException | RuntimeException | ErrorException $e) {
        $log->error('Error in connection to AMQP ' . $e->getMessage());
        return;
    }

    $channel = $connection->channel();

    Vtiger_AmpqHelper_Helper::initNotifications($channel);
    Vtiger_AmpqHelper_Helper::registerShutdown($connection, $channel);

    $data = array(
        'uuid' => uniqid('', true),
        'job' => 'App\Jobs\ReceiveContactsJob',
        'data' => $myModuleInstance->getData(),
    );

    $message = new AMQPMessage(
        json_encode($data),
        [
            'content_type' => 'text/plain',
            'delivery_mode' => 2
        ]
    );

    try {
        $channel->basic_publish($message,  Vtiger_AmpqHelper_Helper::EXCHANGE_NOTIFICATIONS);
    } catch (Exception $e) {
        $log->error('AMQP publish failed: ' . $e->getMessage());
    }
}