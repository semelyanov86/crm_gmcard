<?php

namespace handlers;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;


require_once 'include/events/VTEventHandler.inc';
require_once 'modules/Vtiger/helpers/AmpqHelper.php';

class SendUpdates extends \VTEventHandler
{
    public function handleEvent($eventName, $entityData)
    {
        $moduleName = $entityData->getModuleName();
        if ($eventName == 'vtiger.entity.beforesave' && $moduleName === 'Contacts') {
            $this->triggerUpdatesHandler($entityData);
        }
    }

    protected function triggerUpdatesHandler(\VTEntityData $entityData): bool
    {
        global $rabbitData;
        global $log;
        $recordId = $entityData->getId();

        if (!$recordId) {
            return false;
        }

        $data = $entityData->getData()->getChanged();
        $contactData = [];

        foreach ($data as $field) {
            $contactData[$field] = $_REQUEST[$field];
        }

        $contactData['id'] = $recordId;

        try {
            $connection = new AMQPStreamConnection($rabbitData['host'], $rabbitData['port'], $rabbitData['user'], $rabbitData['password'], $rabbitData['vhost']);
        } catch (AMQPRuntimeException | \RuntimeException | \ErrorException $e) {
            $log->error('AMQP connection error: ' . $e->getMessage());
            return false;
        }

        $channel = $connection->channel();
        \Vtiger_AmpqHelper_Helper::initNotifications($channel);
        \Vtiger_AmpqHelper_Helper::registerShutdown($connection, $channel);

        $data = [
            'uuid' => uniqid('', true),
            'job' => 'App\Jobs\ReceiveContactsJob',
            'data' => $contactData,
        ];

        $message = new AMQPMessage(
            json_encode($data),
            [
                'content_type' => 'text/plain'
            ]
        );

        try {
            $channel->basic_publish($message, \Vtiger_AmpqHelper_Helper::EXCHANGE_NOTIFICATIONS);
        } catch (\Exception $e) {
            $log->error('AMQP publish failed: ' . $e->getMessage());
            return false;
        }

        return true;
    }
}