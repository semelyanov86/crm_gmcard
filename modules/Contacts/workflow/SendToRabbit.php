<?php

require_once 'modules/Vtiger/helpers/AmpqHelper.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
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
         $connection = new AMQPStreamConnection($rabbitData['host'], $rabbitData['port'], $rabbitData['user'], $rabbitData['password'], $rabbitData['vhost']);
     } catch (AMQPRuntimeException | RuntimeException | ErrorException $e) {
         $log->error('Error in connection to AMQP ' . $e->getMessage());
         die;
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
         ['content_type' => 'text/plain']
     );

     $channel->basic_publish($message,  Vtiger_AmpqHelper_Helper::EXCHANGE_NOTIFICATIONS);
}