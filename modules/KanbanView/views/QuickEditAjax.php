<?php

class KanbanView_QuickEditAjax_View extends Vtiger_IndexAjax_View
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        if (!Users_Privileges_Model::isPermitted($moduleName, 'EditView')) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
        }
    }

    public function process(Vtiger_Request $request)
    {
        global $vtiger_current_version;
        $recordId = $request->get('record');
        $recordModel = Vtiger_Record_Model::getInstanceById($recordId);
        $moduleModel = $recordModel->getModule();
        $fieldList = $moduleModel->getFields();
        $requestFieldList = array_intersect_key($request->getAll(), $fieldList);
        foreach ($requestFieldList as $fieldName => $fieldValue) {
            $fieldModel = $fieldList[$fieldName];
            if ($fieldModel->isEditable()) {
                $recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
            }
        }
        $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_QUICKCREATE);
        $picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource('HelpDesk');
        $viewer = $this->getViewer($request);
        if (version_compare($vtiger_current_version, '7.0.0', '>=')) {
            $fieldsInfo = [];
            foreach ($fieldList as $name => $model) {
                $fieldsInfo[$name] = $model->getFieldInfo();
            }
            $viewer->assign('FIELDS_INFO', json_encode($fieldsInfo));
        }
        $viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Zend_Json::encode($picklistDependencyDatasource));
        $viewer->assign('CURRENTDATE', date('Y-n-j'));
        $viewer->assign('MODULE', $request->get('source_module'));
        $viewer->assign('RECORD', $recordId);
        $viewer->assign('SINGLE_MODULE', 'SINGLE_' . $request->get('source_module'));
        $viewer->assign('MODULE_MODEL', $moduleModel);
        $viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
        $viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
        $viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
        $viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
        $viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
        echo $viewer->view('QuickEdit.tpl', 'KanbanView', true);
    }

    public function getHeaderScripts(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $jsFileNames = ['modules.' . $moduleName . '.resources.Edit'];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);

        return $jsScriptInstances;
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}
