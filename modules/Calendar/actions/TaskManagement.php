<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Calendar_TaskManagement_Action extends Calendar_SaveAjax_Action
{
    public function __construct()
    {
        $this->exposeMethod('addTask');
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        if (!empty($mode) && $this->isMethodExposed($mode)) {
            $this->invokeExposedMethod($mode, $request);

            return;
        }
    }

    public function addTask(Vtiger_Request $request)
    {
        $user = Users_Record_Model::getCurrentUserModel();
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);

        $madatoryFields = $moduleModel->getMandatoryFieldModels();
        foreach ($madatoryFields as $mandatoryField) {
            $fieldName = $mandatoryField->getName();

            if ($fieldName == 'date_start') {
                $dateObject = new DateTimeImmutable();
                $date_start = $dateObject->format('Y-m-d');
                $request->set('date_start', $date_start);
            } elseif ($fieldName == 'time_start') {
                $request->set('time_start', $user->get('start_hour')); /* use day-start as time */
            } else {
                if ($request->get($fieldName) == null) {
                    $fieldValue = Vtiger_Util_Helper::fillMandatoryFields($fieldName, $moduleName, 'CRM');
                    $request->set($fieldName, $fieldValue);
                }
            }
        }
        parent::process($request);
    }
}
