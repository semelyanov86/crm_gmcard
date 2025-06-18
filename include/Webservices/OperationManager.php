<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

function setBuiltIn($json)
{
    Zend_Json::$useBuiltinEncoderDecoder = true;
}

class OperationManager
{
    private $format;

    private $formatsData = [
        'json' => [
            'includePath' => 'include/Zend/Json.php',
            'class' => 'Zend_Json',
            'encodeMethod' => 'encode',
            'decodeMethod' => 'decode',
            'postCreate' => 'setBuiltIn',
        ],
    ];

    private $operationMeta;

    private $formatObjects;

    private $inParamProcess;

    private $sessionManager;

    private $pearDB;

    private $operationName;

    private $type;

    private $handlerPath;

    private $handlerMethod;

    private $preLogin;

    private $operationId;

    private $operationParams;

    public function __construct($adb, $operationName, $format, $sessionManager)
    {
        $this->format = strtolower($format);
        $this->sessionManager = $sessionManager;
        $this->formatObjects = [];

        foreach ($this->formatsData as $frmt => $frmtData) {
            require_once $frmtData['includePath'];
            $instance = new $frmtData['class']();
            $this->formatObjects[$frmt]['encode'] = [&$instance, $frmtData['encodeMethod']];
            $this->formatObjects[$frmt]['decode'] = [&$instance, $frmtData['decodeMethod']];
            if ($frmtData['postCreate']) {
                call_user_func($frmtData['postCreate'], $instance);
            }
        }

        $this->pearDB = $adb;
        $this->operationName = $operationName;
        $this->inParamProcess = [];
        $this->inParamProcess['encoded'] = &$this->formatObjects[$this->format]['decode'];
        $this->fillOperationDetails($operationName);
    }

    public function OperationManager($adb, $operationName, $format, $sessionManager)
    {
        // PHP4-style constructor.
        // This will NOT be invoked, unless a sub-class that extends `foo` calls it.
        // In that case, call the new-style constructor to keep compatibility.
        self::__construct($adb, $operationName, $format, $sessionManager);

    }

    public function isPreLoginOperation()
    {
        return $this->preLogin == 1;
    }

    private function fillOperationDetails($operationName)
    {
        $sql = 'select * from vtiger_ws_operation where name=?';
        $result = $this->pearDB->pquery($sql, [$operationName]);
        if ($result) {
            $rowCount = $this->pearDB->num_rows($result);
            if ($rowCount > 0) {
                $row = $this->pearDB->query_result_rowdata($result, 0);
                $this->type = $row['type'];
                $this->handlerMethod = $row['handler_method'];
                $this->handlerPath = $row['handler_path'];
                $this->preLogin = $row['prelogin'];
                $this->operationName = $row['name'];
                $this->operationId = $row['operationid'];
                $this->fillOperationParameters();

                return;
            }
        }

        throw new WebServiceException(WebServiceErrorCode::$UNKNOWNOPERATION, 'Unknown operation requested');
    }

    private function fillOperationParameters()
    {
        $sql = 'select * from vtiger_ws_operation_parameters where operationid=? order by sequence';
        $result = $this->pearDB->pquery($sql, [$this->operationId]);
        $this->operationParams = [];
        if ($result) {
            $rowCount = $this->pearDB->num_rows($result);
            if ($rowCount > 0) {
                for ($i = 0; $i < $rowCount; ++$i) {
                    $row = $this->pearDB->query_result_rowdata($result, $i);
                    array_push($this->operationParams, [$row['name'] => $row['type']]);
                }
            }
        }
    }

    public function getOperationInput()
    {
        $type = strtolower($this->type);
        switch ($type) {
            case 'get': $input = &$_GET;

                return $input;
            case 'post': $input = &$_POST;

                return $input;

            default: $input = &$_REQUEST;

                return $input;
        }
    }

    public function sanitizeOperation($input)
    {
        return $this->sanitizeInputForType($input);
    }

    public function sanitizeInputForType($input)
    {

        $sanitizedInput = [];
        foreach ($this->operationParams as $ind => $columnDetails) {
            foreach ($columnDetails as $columnName => $type) {
                $sanitizedInput[$columnName] = $this->handleType($type, vtws_getParameter($input, $columnName));
            }
        }

        return $sanitizedInput;
    }

    public function handleType($type, $value)
    {
        $result = null;
        $value = $value ? stripslashes($value) : '';
        $type = strtolower($type);
        if (isset($this->inParamProcess[$type]) && $this->inParamProcess[$type]) {
            $result = call_user_func($this->inParamProcess[$type], $value);
        } else {
            $result = $value;
        }

        return $result;
    }

    public function runOperation($params, $user)
    {
        global $API_VERSION;

        try {
            $operation = strtolower($this->operationName);
            if (!$this->preLogin) {
                $params['user'] = $user;

                return call_user_func_array($this->handlerMethod, $params);
            }

            /* PHP 8.x fix to match target handler arguments (named parameter) */
            if ($this->handlerMethod == 'vtws_login') {
                if (isset($params['accessKey'])) {
                    $params['pwd'] = $params['accessKey'];
                    unset($params['accessKey']);
                }
            }

            $userDetails = call_user_func_array($this->handlerMethod, $params);
            if (is_array($userDetails)) {
                return $userDetails;
            }
            $this->sessionManager->set('authenticatedUserId', $userDetails->id);
            global $adb;
            $webserviceObject = VtigerWebserviceObject::fromName($adb, 'Users');
            $userId = vtws_getId($webserviceObject->getEntityId(), $userDetails->id);
            $vtigerVersion = vtws_getVtigerVersion();
            $userInfo = [
                'username' => $userDetails->user_name,
                'first_name' => $userDetails->first_name,
                'last_name' => $userDetails->last_name,
                'email' => $userDetails->email1,
                'time_zone' => $userDetails->time_zone,
                'hour_format' => $userDetails->hour_format,
                'date_format' => $userDetails->date_format,
                'is_admin' => $userDetails->is_admin,
                'call_duration' => $userDetails->callduration,
                'other_event_duration' => $userDetails->othereventduration,
                'sessionName' => $this->sessionManager->getSessionId(),
                'userId' => $userId,
                'version' => $API_VERSION,
                'vtigerVersion' => $vtigerVersion,
            ];

            return $userInfo;


        } catch (DuplicateException $e) {
            throw $e;
        } catch (WebServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unknown Error while processing request');
        }

    }

    public function encode($param)
    {
        return call_user_func($this->formatObjects[$this->format]['encode'], $param);
    }

    public function getOperationIncludes()
    {
        $includes = [];
        array_push($includes,$this->handlerPath);

        return $includes;
    }
}
