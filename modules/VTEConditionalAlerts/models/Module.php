<?php

require_once "modules/com_vtiger_workflow/include.inc";
require_once "modules/com_vtiger_workflow/expression_engine/VTExpressionsManager.inc";

class VTEConditionalAlerts_Module_Model extends Vtiger_Module_Model
{
    public static $metaVariables = array("Current Date" => "(general : (__VtigerMeta__) date) (\$_DATE_FORMAT_)", "Current Time" => "(general : (__VtigerMeta__) time)", "System Timezone" => "(general : (__VtigerMeta__) dbtimezone)", "User Timezone" => "(general : (__VtigerMeta__) usertimezone)", "CRM Detail View URL" => "(general : (__VtigerMeta__) crmdetailviewurl)", "Portal Detail View URL" => "(general : (__VtigerMeta__) portaldetailviewurl)", "Site Url" => "(general : (__VtigerMeta__) siteurl)", "Portal Url" => "(general : (__VtigerMeta__) portalurl)", "Record Id" => "(general : (__VtigerMeta__) recordId)", "LBL_HELPDESK_SUPPORT_NAME" => "(general : (__VtigerMeta__) supportName)", "LBL_HELPDESK_SUPPORT_EMAILID" => "(general : (__VtigerMeta__) supportEmailid)");
    public function getSettingLinks()
    {
        $settingsLinks[] = array("linktype" => "MODULESETTING", "linklabel" => "Settings", "linkurl" => "index.php?module=VTEConditionalAlerts&parent=Settings&view=ListAll&mode=listAll", "linkicon" => "");
        $settingsLinks[] = array("linktype" => "MODULESETTING", "linklabel" => "Uninstall", "linkurl" => "index.php?module=VTEConditionalAlerts&parent=Settings&view=Uninstall", "linkicon" => "");
        return $settingsLinks;
    }
    public static function getSupportedModules()
    {
        $moduleModels = Vtiger_Module_Model::getAll(array(0, 2));
        $supportedModuleModels = array();
        foreach ($moduleModels as $tabId => $moduleModel) {
            if ($moduleModel->isWorkflowSupported() && $moduleModel->getName() != "Webmails" && $moduleModel->getName() != "Events") {
                $supportedModuleModels[$tabId] = $moduleModel;
            }
        }
        return $supportedModuleModels;
    }
    public static function getExpressions()
    {
        $db = PearDatabase::getInstance();
        $mem = new VTExpressionsManager($db);
        return $mem->expressionFunctions();
    }
    public static function getMetaVariables()
    {
        return self::$metaVariables;
    }
    public static function transformToAdvancedFilterCondition($conditions)
    {
        $transformedConditions = array();
        if (!empty($conditions)) {
            if ($conditions[1]) {
                $p_info = $conditions[1]["columns"];
                foreach ($p_info as $index => $info) {
                    $firstGroup[] = array("columnname" => $info["columnname"], "comparator" => $info["comparator"], "value" => $info["value"], "column_condition" => $info["column_condition"], "valuetype" => $info["valuetype"], "groupid" => $info["groupid"]);
                }
            }
            if ($conditions[2]) {
                $p_info = $conditions[2]["columns"];
                foreach ($p_info as $index => $info) {
                    $secondGroup[] = array("columnname" => $info["columnname"], "comparator" => $info["comparator"], "value" => $info["value"], "column_condition" => $info["column_condition"], "valuetype" => $info["valuetype"], "groupid" => $info["groupid"]);
                }
            }
        }
        $transformedConditions[1] = array("columns" => $firstGroup);
        $transformedConditions[2] = array("columns" => $secondGroup);
        return $transformedConditions;
    }
}

?>