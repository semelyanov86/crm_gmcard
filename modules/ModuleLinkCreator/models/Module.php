<?php

include_once 'modules/ModuleLinkCreator/models/ModuleLinkCreatorModule.php';

/**
 * Class ModuleLinkCreator_Module_Model.
 */
class ModuleLinkCreator_Module_Model extends ModuleLinkCreatorModule_Module_Model
{
    public function vteLicense()
    {
        return true;
    }

    /**
     * Function to get Settings links for admin user.
     * @return array
     */
    public function getSettingLinks()
    {
        $settingsLinks[] = ['linktype' => 'MODULESETTING', 'linklabel' => vtranslate('Settings', $this->name), 'linkurl' => 'index.php?module=' . $this->name . '&view=List', 'linkicon' => ''];
        $settingsLinks[] = ['linktype' => 'MODULESETTING', 'linklabel' => vtranslate('Uninstall', $this->name), 'linkurl' => 'index.php?module=' . $this->name . '&parent=Settings&view=Uninstall', 'linkicon' => ''];

        return $settingsLinks;
    }

    /**
     * @return array
     */
    public function getRelation_1_1()
    {
        global $adb;
        $sql = "SELECT\r\n                  vtiger_fieldmodulerel.*,\r\n                  vtiger_tab.tabid            AS tabid,\r\n                  vtiger_related_tab.tabid    AS related_tabid,\r\n                  vtiger_tab.name             AS tab_name,\r\n                  vtiger_tab.tablabel         AS tab_tablabel,\r\n                  vtiger_related_tab.name     AS related_tab_name,\r\n                  vtiger_related_tab.tablabel AS related_tab_tablabel\r\n                FROM vtiger_fieldmodulerel\r\n                  JOIN vtiger_tab AS vtiger_tab ON (vtiger_fieldmodulerel.module LIKE vtiger_tab.name)\r\n                  JOIN vtiger_tab AS vtiger_related_tab ON (vtiger_fieldmodulerel.relmodule LIKE vtiger_related_tab.name)";
        $params = [];
        $result = $adb->pquery($sql, $params);
        $tmpData = [];
        $relations = [];
        if ($adb->num_rows($result)) {
            while ($row = $adb->fetch_array($result)) {
                $relations[] = ['fieldid' => $row['fieldid'], 'module' => $row['module'], 'relmodule' => $row['relmodule'], 'sequence' => $row['sequence'], 'tabid' => $row['tabid'], 'related_tabid' => $row['related_tabid'], 'tab_name' => $row['tab_name'], 'tab_tablabel' => vtranslate($row['tab_tablabel'], $row['tab_name']), 'related_tab_name' => $row['related_tab_name'], 'related_tab_tablabel' => vtranslate($row['related_tab_tablabel'], $row['related_tab_name'])];
            }
        }
        foreach ($relations as $rel) {
            $index = $rel['module'] . '_' . $rel['relmodule'];
            $indexRevert = $rel['relmodule'] . '_' . $rel['module'];
            if (!array_key_exists($index, $tmpData) && !array_key_exists($indexRevert, $tmpData)) {
                $tmpData[$index] = $rel;
            }
            if (!isset($tmpData[$index])) {
                continue;
            }
            $tmpData[$index]['relations'][$rel['fieldid']] = $rel;
            foreach ($relations as $tmpRel) {
                if ($rel['module'] == $tmpRel['relmodule'] && $rel['relmodule'] == $tmpRel['module'] || $rel['module'] == $tmpRel['module'] && $rel['relmodule'] == $tmpRel['relmodule']) {
                    $tmpData[$index]['relations'][$tmpRel['fieldid']] = $tmpRel;
                }
            }
        }
        $data = [];
        foreach ($tmpData as $rel) {
            if (count($rel['relations']) > 1) {
                $data[] = $rel;
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getRelation_1_M()
    {
        global $adb;
        global $vtiger_current_version;
        $nameRelatedList = ['get_dependents_list', 'get_activities', 'get_related_list'];
        if (version_compare($vtiger_current_version, '7.0.0', '>=')) {
            $sql = "SELECT\r\n                  vtiger_relatedlists.*,\r\n                  vtiger_tab.name             AS tab_name,\r\n                  vtiger_tab.tablabel         AS tab_tablabel,\r\n                  vtiger_related_tab.name     AS related_tab_name,\r\n                  vtiger_related_tab.tablabel AS related_tab_tablabel,\r\n                    vtiger_field.fieldname\r\n                FROM vtiger_relatedlists\r\n                  JOIN vtiger_tab AS vtiger_tab ON (vtiger_relatedlists.tabid = vtiger_tab.tabid)\r\n                  JOIN vtiger_tab AS vtiger_related_tab ON (vtiger_relatedlists.related_tabid = vtiger_related_tab.tabid)\r\n                    LEFT JOIN vtiger_field ON vtiger_field.fieldid=vtiger_relatedlists.relationfieldid\r\n                WHERE vtiger_relatedlists.name IN (" . generateQuestionMarks($nameRelatedList) . ')  ORDER BY vtiger_tab.tablabel;';
        } else {
            $sql = "SELECT\r\n                  vtiger_relatedlists.*,\r\n                  vtiger_tab.name             AS tab_name,\r\n                  vtiger_tab.tablabel         AS tab_tablabel,\r\n                  vtiger_related_tab.name     AS related_tab_name,\r\n                  vtiger_related_tab.tablabel AS related_tab_tablabel\r\n                FROM vtiger_relatedlists\r\n                  JOIN vtiger_tab AS vtiger_tab ON (vtiger_relatedlists.tabid = vtiger_tab.tabid)\r\n                  JOIN vtiger_tab AS vtiger_related_tab ON (vtiger_relatedlists.related_tabid = vtiger_related_tab.tabid)\r\n                WHERE vtiger_relatedlists.name IN (" . generateQuestionMarks($nameRelatedList) . ')  ORDER BY vtiger_tab.tablabel;';
        }
        $result = $adb->pquery($sql, $nameRelatedList);
        $tmpData = [];
        $relations = [];
        if ($adb->num_rows($result)) {
            while ($row = $adb->fetch_array($result)) {
                $relations[] = ['relation_id' => $row['relation_id'], 'tabid' => $row['tabid'], 'name' => $row['name'], 'sequence' => $row['sequence'], 'label' => $row['label'], 'tab_name' => $row['tab_name'], 'fieldname' => $row['fieldname'], 'tab_tablabel' => vtranslate($row['tab_tablabel'], $row['tab_name']), 'related_tabid' => $row['related_tabid'], 'related_tab_name' => $row['related_tab_name'], 'related_tab_tablabel' => vtranslate($row['related_tab_tablabel'], $row['related_tab_name'])];
            }
        }
        foreach ($relations as $rel) {
            $index = $rel['tab_name'] . '_' . $rel['related_tab_name'];
            if (!array_key_exists($index, $tmpData)) {
                $tmpData[$index] = $rel;
            }
            $tmpData[$index]['relations'][$rel['relation_id']] = $rel;
        }
        $data = [];
        foreach ($tmpData as $rel) {
            $data[] = $rel;
        }

        return $data;
    }

    public function getRelation_1_None()
    {
        global $adb;
        $sql = "SELECT vtiger_field.fieldid,vtiger_field.fieldlabel, vtiger_fieldmodulerel.module, vtiger_fieldmodulerel.relmodule FROM `vtiger_field` \r\n                INNER JOIN vtiger_fieldmodulerel on vtiger_field.fieldid = vtiger_fieldmodulerel.fieldid\r\n                WHERE vtiger_field.fieldname LIKE 'cf_nrl_%' AND vtiger_field.presence != 1";
        $result = $adb->pquery($sql, []);
        $tmpData = [];
        $relations = [];
        if ($adb->num_rows($result)) {
            while ($row = $adb->fetch_array($result)) {
                $relations[] = ['fieldid' => $row['fieldid'], 'fieldlabel' => $row['fieldlabel'], 'module1' => vtranslate($row['module'], $row['module']), 'module2' => vtranslate($row['relmodule'], $row['relmodule'])];
            }
        }

        return $relations;
    }

    /**
     * @return array
     */
    public function getRelation_M_M()
    {
        global $adb;
        $sql = "SELECT\r\n                  vtiger_relatedlists.*,\r\n                  vtiger_tab.name             AS tab_name,\r\n                  vtiger_tab.tablabel         AS tab_tablabel,\r\n                  vtiger_related_tab.name     AS related_tab_name,\r\n                  vtiger_related_tab.tablabel AS related_tab_tablabel\r\n                FROM vtiger_relatedlists\r\n                  JOIN vtiger_tab AS vtiger_tab ON (vtiger_relatedlists.tabid = vtiger_tab.tabid)\r\n                  JOIN vtiger_tab AS vtiger_related_tab ON (vtiger_relatedlists.related_tabid = vtiger_related_tab.tabid)\r\n                WHERE vtiger_relatedlists.name LIKE ?;";
        $params = ['get_related_list'];
        $result = $adb->pquery($sql, $params);
        $tmpData = [];
        $relations = [];
        if ($adb->num_rows($result)) {
            while ($row = $adb->fetch_array($result)) {
                $relations[] = ['relation_id' => $row['relation_id'], 'tabid' => $row['tabid'], 'sequence' => $row['sequence'], 'label' => $row['label'], 'tab_name' => $row['tab_name'], 'tab_tablabel' => vtranslate($row['tab_tablabel'], $row['tab_name']), 'related_tabid' => $row['related_tabid'], 'related_tab_name' => $row['related_tab_name'], 'related_tab_tablabel' => vtranslate($row['related_tab_tablabel'], $row['related_tab_name'])];
            }
        }
        foreach ($relations as $rel) {
            $index = $rel['tabid'] . '_' . $rel['related_tabid'];
            $indexRevert = $rel['related_tabid'] . '_' . $rel['tabid'];
            if (!array_key_exists($index, $tmpData) && !array_key_exists($indexRevert, $tmpData)) {
                $tmpData[$index] = $rel;
            }
            if (!isset($tmpData[$index])) {
                continue;
            }
            $tmpData[$index]['relations'][$rel['relation_id']] = $rel;
            foreach ($relations as $tmpRel) {
                if ($rel['tabid'] == $tmpRel['related_tabid'] && $rel['related_tabid'] == $tmpRel['tabid'] || $rel['tabid'] == $tmpRel['tabid'] && $rel['related_tabid'] == $tmpRel['related_tabid']) {
                    $tmpData[$index]['relations'][$tmpRel['relation_id']] = $tmpRel;
                }
            }
        }
        $data = [];
        foreach ($tmpData as $rel) {
            if (count($rel['relations']) > 1) {
                $data[] = $rel;
            }
        }

        return $data;
    }

    public function getSettingsActiveBlock($viewName)
    {
        $blocksList = [];

        return $blocksList[$viewName];
    }
}
