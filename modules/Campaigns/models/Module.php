<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Campaigns_Module_Model extends Vtiger_Module_Model
{
    /**
     * Function to get Specific Relation Query for this Module.
     * @param <type> $relatedModule
     * @return <type>
     */
    public function getSpecificRelationQuery($relatedModule)
    {
        if ($relatedModule === 'Leads') {
            $specificQuery = 'AND vtiger_leaddetails.converted = 0';

            return $specificQuery;
        }

        return parent::getSpecificRelationQuery($relatedModule);
    }

    /**
     * Function to check whether the module is summary view supported.
     * @return <Boolean> - true/false
     */
    public function isSummaryViewSupported()
    {
        return false;
    }

    /**
     * Function to get list view query for popup window.
     * @param <String> $sourceModule Parent module
     * @param <String> $field parent fieldname
     * @param <Integer> $record parent id
     * @param <String> $listQuery
     * @return <String> Listview Query
     */
    public function getQueryByModuleField($sourceModule, $field, $record, $listQuery)
    {
        if (in_array($sourceModule, ['Leads', 'Accounts', 'Contacts'])) {
            switch ($sourceModule) {
                case 'Leads': $tableName = 'vtiger_campaignleadrel';
                    $relatedFieldName = 'leadid';
                    break;
                case 'Accounts': $tableName = 'vtiger_campaignaccountrel';
                    $relatedFieldName = 'accountid';
                    break;
                case 'Contacts': $tableName = 'vtiger_campaigncontrel';
                    $relatedFieldName = 'contactid';
                    break;
            }
            $db = PearDatabase::getInstance();
            $condition = " vtiger_campaign.campaignid NOT IN (SELECT campaignid FROM {$tableName} WHERE {$relatedFieldName} = ?)";
            $condition = $db->convert2Sql($condition, [$record]);
            $pos = stripos($listQuery, 'where');

            if ($pos) {
                $split = preg_split('/where/i', $listQuery);
                $overRideQuery = $split[0] . ' WHERE ' . $split[1] . ' AND ' . $condition;
            } else {
                $overRideQuery = $listQuery . ' WHERE ' . $condition;
            }

            return $overRideQuery;
        }
    }

    /**
     * Function is used to give links in the All menu bar.
     */
    public function getQuickMenuModels()
    {
        if ($this->isEntityModule()) {
            $moduleName = $this->getName();
            $listViewModel = Vtiger_ListView_Model::getCleanInstance($moduleName);
            $basicListViewLinks = $listViewModel->getBasicLinks();
        }

        if ($basicListViewLinks) {
            foreach ($basicListViewLinks as $basicListViewLink) {
                if (is_array($basicListViewLink)) {
                    $links[] = Vtiger_Link_Model::getInstanceFromValues($basicListViewLink);
                } elseif (is_a($basicListViewLink, 'Vtiger_Link_Model')) {
                    $links[] = $basicListViewLink;
                }
            }
        }

        return $links;
    }

    /*
     * Function to get supported utility actions for a module
     */
    public function getUtilityActionsNames()
    {
        return [];
    }
}
