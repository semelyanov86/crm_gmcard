<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Products_DetailView_Model extends Vtiger_DetailView_Model
{
    /**
     * Function to get the detail view links (links and widgets).
     * @param <array> $linkParams - parameters which will be used to calicaulate the params
     * @return <array> - array of link models in the format as below
     *                   array('linktype'=>list of link models);
     */
    public function getDetailViewLinks($linkParams)
    {
        $currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

        $linkModelList = parent::getDetailViewLinks($linkParams);
        $recordModel = $this->getRecord();

        if ($recordModel->getActiveStatusOfRecord()) {
            $quotesModuleModel = Vtiger_Module_Model::getInstance('Quotes');
            if ($currentUserModel->hasModuleActionPermission($quotesModuleModel->getId(), 'CreateView')) {
                $basicActionLink = [
                    'linktype' => 'DETAILVIEW',
                    'linklabel' => vtranslate('LBL_CREATE') . ' ' . vtranslate($quotesModuleModel->getSingularLabelKey(), 'Quotes'),
                    'linkurl' => $recordModel->getCreateQuoteUrl(),
                    'linkicon' => '',
                ];
                $linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
            }

            $invoiceModuleModel = Vtiger_Module_Model::getInstance('Invoice');
            if ($currentUserModel->hasModuleActionPermission($invoiceModuleModel->getId(), 'CreateView')) {
                $basicActionLink = [
                    'linktype' => 'DETAILVIEW',
                    'linklabel' => vtranslate('LBL_CREATE') . ' ' . vtranslate($invoiceModuleModel->getSingularLabelKey(), 'Invoice'),
                    'linkurl' => $recordModel->getCreateInvoiceUrl(),
                    'linkicon' => '',
                ];
                $linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
            }

            $purchaseOrderModuleModel = Vtiger_Module_Model::getInstance('PurchaseOrder');
            if ($currentUserModel->hasModuleActionPermission($purchaseOrderModuleModel->getId(), 'CreateView')) {
                $basicActionLink = [
                    'linktype' => 'DETAILVIEW',
                    'linklabel' => vtranslate('LBL_CREATE') . ' ' . vtranslate($purchaseOrderModuleModel->getSingularLabelKey(), 'PurchaseOrder'),
                    'linkurl' => $recordModel->getCreatePurchaseOrderUrl(),
                    'linkicon' => '',
                ];
                $linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
            }

            $salesOrderModuleModel = Vtiger_Module_Model::getInstance('SalesOrder');
            if ($currentUserModel->hasModuleActionPermission($salesOrderModuleModel->getId(), 'CreateView')) {
                $basicActionLink = [
                    'linktype' => 'DETAILVIEW',
                    'linklabel' =>  vtranslate('LBL_CREATE') . ' ' . vtranslate($salesOrderModuleModel->getSingularLabelKey(), 'SalesOrder'),
                    'linkurl' => $recordModel->getCreateSalesOrderUrl(),
                    'linkicon' => '',
                ];
                $linkModelList['DETAILVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($basicActionLink);
            }
        }

        return $linkModelList;
    }
}
