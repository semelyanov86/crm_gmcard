<?php

class VTEButtons_QuickEditAjax_View extends Vtiger_QuickCreateAjax_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function process(Vtiger_Request $request)
    {
        global $adb;
        $viewer = $this->getViewer($request);
        $moduleName = $request->get("moduleEditName");
        $recordId = $request->get("record");
        $vteButtonsId = $request->get("vteButtonId");
        $request->set("module", $moduleName);
        $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $fieldList = $moduleModel->getFields();
        $sql = "SELECT * FROM `vte_buttons_settings` WHERE id=?";
        $results = $adb->pquery($sql, array($vteButtonsId));
        if (0 < $adb->num_rows($results)) {
            $strfields = $adb->query_result($results, 0, "field_name");
            $strfields = html_entity_decode($strfields);
            $strfields = str_replace("\"", "", $strfields);
        }
        $requestFieldList = array_intersect_key($request->getAll(), $fieldList);
        foreach ($requestFieldList as $fieldName => $fieldValue) {
            $fieldModel = $fieldList[$fieldName];
            if ($fieldModel->isEditable() && in_array($fieldName, explode(",", $strfields))) {
                $recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
            }
        }
        $picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
        $viewer->assign("PICKIST_DEPENDENCY_DATASOURCE", Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
        $viewer->assign("RECORD_ID", $recordId);
        $viewer->assign("VTEBUTTONS_ID", $vteButtonsId);
        $viewer->assign("RECORD_MODEL", $recordModel);
        $viewer->assign("ALL_FIELDS", $fieldList);
        $viewer->assign("ADD_FIELDS", explode(",", $strfields));
        $viewer->assign("MODULE_NAME", $moduleName);
        $viewer->assign("MODULE_MODEL", $moduleModel);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $viewer->assign("SCRIPTS", $this->getHeaderScripts($request));
        $viewer->assign("MAX_UPLOAD_LIMIT_MB", Vtiger_Util_Helper::getMaxUploadSize());
        $viewer->assign("MAX_UPLOAD_LIMIT", vglobal("upload_maxsize"));
        echo $viewer->view("VTEButtonsQuickEdit.tpl", "VTEButtons", true);
    }
}

?>