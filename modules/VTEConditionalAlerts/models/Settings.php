<?php

class VTEConditionalAlerts_Settings_Model extends Vtiger_Base_Model
{
    public $user;

    public $db;

    public function __construct()
    {
        global $current_user;
        $this->user = $current_user;
        $this->db = PearDatabase::getInstance();
    }

    public function getEntityModulesName()
    {
        $ignore_modules = "'Documents','Calendar','Emails','Commnets','PBXManager','SMSNotifier','Webmails'";
        $result = $this->db->pquery("SELECT vtiger_tab.*\r\n                                    FROM vtiger_tab\r\n                                    WHERE vtiger_tab.isentitytype = 1\r\n                                        AND vtiger_tab.customized = 0\r\n                                        AND vtiger_tab.presence = 0\r\n                                        AND vtiger_tab.name NOT IN( " . $ignore_modules . " )\r\n                                    ORDER BY vtiger_tab.name ", []);
        $arr = [];
        if ($this->db->num_rows($result)) {
            while ($row = $this->db->fetch_array($result)) {
                $row['module_name'] = $row['name'];
                $row['name'] = vtranslate($row['name'], $row['name']);
                $arr[] = $row;
            }
        }

        return $arr;
    }

    public function saveModuleSetting($data)
    {
        $module_setting = $this->getModuleSettings($data['module_name']);
        if (!$module_setting) {
            $this->db->pquery('INSERT INTO vte_document_manager_setting(`module`,`settings`) VALUES(?,?)', [$data['module_name'], $data['module_setting']]);
        } else {
            $this->db->pquery('UPDATE vte_document_manager_setting SET `settings` =  ? WHERE id=?', [$data['module_setting'], $module_setting['id']]);
        }
        $obj_settings = json_decode($data['module_setting']);
        $tabId = getTabid($data['module_name']);
        $url = 'module=VTEConditionalAlerts&view=TreeFolder&mode=showWidget&parent_module=' . $data['module_name'];
        if ($obj_settings->enable_widget == 1) {
            Vtiger_Link::addLink($tabId, 'DETAILVIEWSIDEBARWIDGET', 'Documents', $url);
        } else {
            Vtiger_Link::deleteLink($tabId, 'DETAILVIEWSIDEBARWIDGET', 'Documents', $url);
        }

        return true;
    }

    public function getModuleSettings($moduleName)
    {
        $result = $this->db->pquery("SELECT * FROM vte_document_manager_setting\r\n                                    WHERE vte_document_manager_setting.module LIKE ?\r\n                                    LIMIT 0, 1", [$moduleName]);
        $module_data = [];
        if ($this->db->num_rows($result) > 0) {
            $module_data['id'] = $this->db->query_result($result, 0, 'id');
            $module_data['module'] = $moduleName;
            $module_data['settings'] = $this->db->query_result($result, 0, 'settings');

            return $module_data;
        }

        return false;
    }
}
