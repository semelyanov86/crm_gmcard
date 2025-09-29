<?php

namespace handlers;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;


require_once 'include/events/VTEventHandler.inc';
require_once 'modules/Vtiger/helpers/AmpqHelper.php';
require_once 'data/VTEntityDelta.php';

class SendUpdates extends \VTEventHandler
{
    /**
     * @param string $eventName
     * @param \VTEntityData $entityData
     * @return void
     */
    public function handleEvent($eventName, $entityData)
    {
        $moduleName = $entityData->getModuleName();
        if ($eventName === 'vtiger.entity.aftersave' && $moduleName === 'Contacts') {
            $this->triggerUpdatesHandler($entityData);
        }
    }

    protected function triggerUpdatesHandler(\VTEntityData $entityData): bool
    {
        global $rabbitData;
        global $log;
        $recordId = $entityData->getId();
        $moduleName = $entityData->getModuleName();

        if (!$recordId) {
            return false;
        }

        $deltaHelper = new \VTEntityDelta();
        $delta = $deltaHelper->getEntityDelta($moduleName, $recordId, true) ?: [];

        $contactData = ['id' => $recordId];
        foreach ($delta as $fieldName => $values) {
            if (array_key_exists('currentValue', $values)) {
                $contactData[$fieldName] = $values['currentValue'];
            }
        }

        if (count($contactData) <= 1) {
            return true;
        }

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
            'job' => 'App\\Jobs\\ReceiveContactsJob',
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