<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class ServiceContracts_Module_Model extends Vtiger_Module_Model
{
    /**
     * Function to check whether the module is summary view supported.
     * @return <Boolean> - true/false
     */
    public function isSummaryViewSupported()
    {
        return false;
    }

    /*
     * Function to get supported utility actions for a module
     */
    public function getUtilityActionsNames()
    {
        return ['Import', 'Export', 'DuplicatesHandling'];
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
        if ($sourceModule === 'HelpDesk') {
            $condition = ' vtiger_servicecontracts.servicecontractsid NOT IN (SELECT relcrmid FROM vtiger_crmentityrel WHERE crmid = ? UNION SELECT crmid FROM vtiger_crmentityrel WHERE relcrmid = ?) ';
            $db = PearDatabase::getInstance();
            $condition = $db->convert2Sql($condition, [$record, $record]);

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
}
