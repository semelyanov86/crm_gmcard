<?php

/*
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 */

include_once 'vtlib/Vtiger/PDF/models/Model.php';
include_once 'vtlib/Vtiger/PDF/inventory/HeaderViewer.php';
include_once 'vtlib/Vtiger/PDF/inventory/FooterViewer.php';
include_once 'vtlib/Vtiger/PDF/inventory/ContentViewer.php';
include_once 'vtlib/Vtiger/PDF/inventory/ContentViewer2.php';
include_once 'vtlib/Vtiger/PDF/viewers/PagerViewer.php';
include_once 'vtlib/Vtiger/PDF/PDFGenerator.php';
include_once 'data/CRMEntity.php';
#[AllowDynamicProperties]
class Vtiger_InventoryPDFController
{
    protected $module;

    protected $focus;

    public function __construct($module)
    {
        $this->moduleName = $module;
    }

    public function loadRecord($id)
    {
        global $current_user;
        $this->focus = $focus = CRMEntity::getInstance($this->moduleName);
        $focus->retrieve_entity_info($id, $this->moduleName);
        $focus->apply_field_security();
        $focus->id = $id;
        $this->associated_products = getAssociatedProducts($this->moduleName, $focus);
    }

    public function getPDFGenerator()
    {
        return new Vtiger_PDF_Generator();
    }

    public function getContentViewer()
    {
        if ($this->focusColumnValue('hdnTaxType') == 'individual') {
            $contentViewer = new Vtiger_PDF_InventoryContentViewer();
        } else {
            $contentViewer = new Vtiger_PDF_InventoryTaxGroupContentViewer();
        }
        $contentViewer->setContentModels($this->buildContentModels());
        $contentViewer->setSummaryModel($this->buildSummaryModel());
        $contentViewer->setLabelModel($this->buildContentLabelModel());
        $contentViewer->setWatermarkModel($this->buildWatermarkModel());

        return $contentViewer;
    }

    public function getHeaderViewer()
    {
        $headerViewer = new Vtiger_PDF_InventoryHeaderViewer();
        $headerViewer->setModel($this->buildHeaderModel());

        return $headerViewer;
    }

    public function getFooterViewer()
    {
        $footerViewer = new Vtiger_PDF_InventoryFooterViewer();
        $footerViewer->setModel($this->buildFooterModel());
        $footerViewer->setLabelModel($this->buildFooterLabelModel());
        $footerViewer->setOnLastPage();

        return $footerViewer;
    }

    public function getPagerViewer()
    {
        $pagerViewer = new Vtiger_PDF_PagerViewer();
        $pagerViewer->setModel($this->buildPagermodel());

        return $pagerViewer;
    }

    public function Output($filename, $type)
    {
        if (is_null($this->focus)) {
            return;
        }

        $pdfgenerator = $this->getPDFGenerator();

        $pdfgenerator->setPagerViewer($this->getPagerViewer());
        $pdfgenerator->setHeaderViewer($this->getHeaderViewer());
        $pdfgenerator->setFooterViewer($this->getFooterViewer());
        $pdfgenerator->setContentViewer($this->getContentViewer());

        $pdfgenerator->generate($filename, $type);
    }


    // Helper methods

    public function buildContentModels()
    {
        $associated_products = $this->associated_products;
        $contentModels = [];
        $productLineItemIndex = 0;
        $totaltaxes = 0;
        $no_of_decimal_places = getCurrencyDecimalPlaces();
        foreach ($associated_products as $productLineItem) {
            ++$productLineItemIndex;

            $contentModel = new Vtiger_PDF_Model();

            $discountPercentage  = 0.00;
            $total_tax_percent = 0.00;
            $producttotal_taxes = 0.00;
            $quantity = '';
            $listPrice = '';
            $discount = '';
            $taxable_total = '';
            $tax_amount = '';
            $producttotal = '';


            $quantity	= $productLineItem["qty{$productLineItemIndex}"];
            $listPrice	= $productLineItem["listPrice{$productLineItemIndex}"];
            $discount	= $productLineItem["discountTotal{$productLineItemIndex}"];
            $taxable_total = $quantity * $listPrice - $discount;
            $taxable_total = number_format($taxable_total, $no_of_decimal_places, '.', '');
            $producttotal = $taxable_total;
            if ($this->focus->column_fields['hdnTaxType'] == 'individual') {
                foreach ($productLineItem['taxes'] as $tax_count => $productLinetItemTaxInfo) {
                    $tax_percent = $productLineItem['taxes'][$tax_count]['percentage'];
                    $total_tax_percent += $tax_percent;
                    $tax_amount = (($taxable_total * $tax_percent) / 100);
                    $producttotal_taxes += $tax_amount;
                }
            }

            $producttotal_taxes = number_format($producttotal_taxes, $no_of_decimal_places, '.', '');
            $producttotal = $taxable_total + $producttotal_taxes;
            $producttotal = number_format($producttotal, $no_of_decimal_places, '.', '');
            $tax = $producttotal_taxes;
            $totaltaxes += $tax;
            $totaltaxes = number_format($totaltaxes, $no_of_decimal_places, '.', '');
            $discountPercentage = $productLineItem["discount_percent{$productLineItemIndex}"];
            $productName = decode_html($productLineItem["productName{$productLineItemIndex}"]);
            // get the sub product
            $subProducts = $productLineItem["subProductArray{$productLineItemIndex}"] ?? '';
            if ($subProducts != '') {
                foreach ($subProducts as $subProduct) {
                    $productName .= "\n" . ' - ' . decode_html($subProduct);
                }
            }
            $contentModel->set('Name', $productName);
            $contentModel->set('Code', decode_html($productLineItem["hdnProductcode{$productLineItemIndex}"]));
            $contentModel->set('Quantity', $quantity);
            $contentModel->set('Price', $this->formatPrice($listPrice));
            $contentModel->set('Discount', $this->formatPrice($discount) . "\n ({$discountPercentage}%)");
            $contentModel->set('Tax', $this->formatPrice($tax) . "\n ({$total_tax_percent}%)");
            $contentModel->set('Total', $this->formatPrice($producttotal));
            $contentModel->set('Comment', decode_html($productLineItem["comment{$productLineItemIndex}"]));

            $contentModels[] = $contentModel;
        }
        $this->totaltaxes = $totaltaxes; // will be used to add it to the net total

        return $contentModels;
    }

    public function buildContentLabelModel()
    {
        $labelModel = new Vtiger_PDF_Model();
        $labelModel->set('Code', getTranslatedString('Product Code', $this->moduleName));
        $labelModel->set('Name', getTranslatedString('Product Name', $this->moduleName));
        $labelModel->set('Quantity', getTranslatedString('Quantity', $this->moduleName));
        $labelModel->set('Price', getTranslatedString('LBL_LIST_PRICE', $this->moduleName));
        $labelModel->set('Discount', getTranslatedString('Discount', $this->moduleName));
        $labelModel->set('Tax', getTranslatedString('Tax', $this->moduleName));
        $labelModel->set('Total', getTranslatedString('Total', $this->moduleName));
        $labelModel->set('Comment', getTranslatedString('Comment'), $this->moduleName);

        return $labelModel;
    }

    public function buildSummaryModel()
    {
        $associated_products = $this->associated_products;
        $final_details = $associated_products[1]['final_details'];

        $summaryModel = new Vtiger_PDF_Model();

        $netTotal = $discount = $handlingCharges =  $handlingTaxes = 0;
        $adjustment = $grandTotal = 0;

        $productLineItemIndex = 0;
        $sh_tax_percent = 0;
        foreach ($associated_products as $productLineItem) {
            ++$productLineItemIndex;
            $netTotal += $productLineItem["netPrice{$productLineItemIndex}"];
        }
        $netTotal = number_format($netTotal + $this->totaltaxes, getCurrencyDecimalPlaces(), '.', '');
        $summaryModel->set(getTranslatedString('Net Total', $this->moduleName), $this->formatPrice($netTotal));

        $discount_amount = $final_details['discount_amount_final'];
        $discount_percent = $final_details['discount_percentage_final'];

        $discount = 0.0;
        $discount_final_percent = '0.00';
        if ($final_details['discount_type_final'] == 'amount') {
            $discount = $discount_amount;
        } elseif ($final_details['discount_type_final'] == 'percentage') {
            $discount_final_percent = $discount_percent;
            $discount = (($discount_percent * $final_details['hdnSubTotal']) / 100);
        }
        $summaryModel->set(getTranslatedString('Discount', $this->moduleName) . "({$discount_final_percent}%)", $this->formatPrice($discount));

        $group_total_tax_percent = '0.00';
        // To calculate the group tax amount
        if ($final_details['taxtype'] == 'group') {
            $group_tax_details = $final_details['taxes'];
            foreach ($group_tax_details as $i => $group_tax_info) {
                $group_total_tax_percent += $group_tax_details[$i]['percentage'] ?? 0.00;
            }
            $summaryModel->set(getTranslatedString('Tax:', $this->moduleName) . "({$group_total_tax_percent}%)", $this->formatPrice($final_details['tax_totalamount']));
        }
        // Shipping & Handling taxes
        $sh_tax_details = $final_details['sh_taxes'];
        foreach ($sh_tax_details as $i => $sh_tax_info) {
            $sh_tax_percent = $sh_tax_percent + $sh_tax_details[$i]['percentage'];
        }
        // obtain the Currency Symbol
        $currencySymbol = $this->buildCurrencySymbol();

        $summaryModel->set(getTranslatedString('Shipping & Handling Charges', $this->moduleName), $this->formatPrice($final_details['shipping_handling_charge']));
        $summaryModel->set(getTranslatedString('Shipping & Handling Tax:', $this->moduleName) . "({$sh_tax_percent}%)", $this->formatPrice($final_details['shtax_totalamount']));
        $summaryModel->set(getTranslatedString('Adjustment', $this->moduleName), $this->formatPrice($final_details['adjustment']));
        $summaryModel->set(getTranslatedString('Grand Total:', $this->moduleName) . "(in {$currencySymbol})", $this->formatPrice($final_details['grandTotal'])); // TODO add currency string

        if ($this->moduleName == 'Invoice') {
            $receivedVal = $this->focusColumnValue('received');
            if (!$receivedVal) {
                $this->focus->column_fields['received'] = 0;
            }
            // If Received value is exist then only Recieved, Balance details should present in PDF
            if ($this->formatPrice($this->focusColumnValue('received')) > 0) {
                $summaryModel->set(getTranslatedString('Received', $this->moduleName), $this->formatPrice($this->focusColumnValue('received')));
                $summaryModel->set(getTranslatedString('Balance', $this->moduleName), $this->formatPrice($this->focusColumnValue('balance')));
            }
        }

        return $summaryModel;
    }

    public function buildHeaderModel()
    {
        $headerModel = new Vtiger_PDF_Model();
        $headerModel->set('title', $this->buildHeaderModelTitle());
        $modelColumns = [$this->buildHeaderModelColumnLeft(), $this->buildHeaderModelColumnCenter(), $this->buildHeaderModelColumnRight()];
        $headerModel->set('columns', $modelColumns);

        return $headerModel;
    }

    public function buildHeaderModelTitle()
    {
        return $this->moduleName;
    }

    public function buildHeaderModelColumnLeft()
    {
        global $adb;

        // Company information
        $result = $adb->pquery('SELECT * FROM vtiger_organizationdetails', []);
        $num_rows = $adb->num_rows($result);
        if ($num_rows) {
            $resultrow = $adb->fetch_array($result);

            $addressValues = [];
            $addressValues[] = $resultrow['address'];
            if (!empty($resultrow['city'])) {
                $addressValues[] = "\n" . $resultrow['city'];
            }
            if (!empty($resultrow['state'])) {
                $addressValues[] = ',' . $resultrow['state'];
            }
            if (!empty($resultrow['code'])) {
                $addressValues[] = $resultrow['code'];
            }
            if (!empty($resultrow['country'])) {
                $addressValues[] = "\n" . $resultrow['country'];
            }

            $additionalCompanyInfo = [];
            if (!empty($resultrow['phone'])) {
                $additionalCompanyInfo[] = "\n" . getTranslatedString('Phone: ', $this->moduleName) . $resultrow['phone'];
            }
            if (!empty($resultrow['fax'])) {
                $additionalCompanyInfo[] = "\n" . getTranslatedString('Fax: ', $this->moduleName) . $resultrow['fax'];
            }
            if (!empty($resultrow['website'])) {
                $additionalCompanyInfo[] = "\n" . getTranslatedString('Website: ', $this->moduleName) . $resultrow['website'];
            }
            if (!empty($resultrow['vatid'])) {
                $additionalCompanyInfo[] = "\n" . getTranslatedString('VAT ID: ', $this->moduleName) . $resultrow['vatid'];
            }

            $modelColumnLeft = [
                'logo' => 'test/logo/' . $resultrow['logoname'],
                'summary' => decode_html($resultrow['organizationname']),
                'content' => decode_html($this->joinValues($addressValues, ' ') . $this->joinValues($additionalCompanyInfo, ' ')),
            ];
        }

        return $modelColumnLeft;
    }

    public function buildHeaderModelColumnCenter()
    {
        $customerName = $this->resolveReferenceLabel($this->focusColumnValue('account_id'), 'Accounts');
        $contactName = $this->resolveReferenceLabel($this->focusColumnValue('contact_id'), 'Contacts');

        $customerNameLabel = getTranslatedString('Customer Name', $this->moduleName);
        $contactNameLabel = getTranslatedString('Contact Name', $this->moduleName);
        $modelColumnCenter = [
            $customerNameLabel => $customerName,
            $contactNameLabel  => $contactName,
        ];

        return $modelColumnCenter;
    }

    public function buildHeaderModelColumnRight()
    {
        $issueDateLabel = getTranslatedString('Issued Date', $this->moduleName);
        $validDateLabel = getTranslatedString('Valid Date', $this->moduleName);
        $billingAddressLabel = getTranslatedString('Billing Address', $this->moduleName);
        $shippingAddressLabel = getTranslatedString('Shipping Address', $this->moduleName);

        $modelColumnRight = [
            'dates' => [
                $issueDateLabel  => $this->formatDate(date('Y-m-d')),
                $validDateLabel  => $this->formatDate($this->focusColumnValue('validtill')),
            ],
            $billingAddressLabel  => $this->buildHeaderBillingAddress(),
            $shippingAddressLabel => $this->buildHeaderShippingAddress(),
        ];

        return $modelColumnRight;
    }

    public function buildFooterModel()
    {
        $footerModel = new Vtiger_PDF_Model();
        $footerModel->set(Vtiger_PDF_InventoryFooterViewer::$DESCRIPTION_DATA_KEY, from_html($this->focusColumnValue('description')));
        $footerModel->set(Vtiger_PDF_InventoryFooterViewer::$TERMSANDCONDITION_DATA_KEY, from_html($this->focusColumnValue('terms_conditions')));

        return $footerModel;
    }

    public function buildFooterLabelModel()
    {
        $labelModel = new Vtiger_PDF_Model();
        $labelModel->set(Vtiger_PDF_InventoryFooterViewer::$DESCRIPTION_LABEL_KEY, getTranslatedString('Description', $this->moduleName));
        $labelModel->set(Vtiger_PDF_InventoryFooterViewer::$TERMSANDCONDITION_LABEL_KEY, getTranslatedString('Terms & Conditions', $this->moduleName));

        return $labelModel;
    }

    public function buildPagerModel()
    {
        $footerModel = new Vtiger_PDF_Model();
        $footerModel->set('format', '-%s-');

        return $footerModel;
    }

    public function getWatermarkContent()
    {
        return '';
    }

    public function buildWatermarkModel()
    {
        $watermarkModel = new Vtiger_PDF_Model();
        $watermarkModel->set('content', $this->getWatermarkContent());

        return $watermarkModel;
    }

    public function buildHeaderBillingAddress()
    {
        $billPoBox	= $this->focusColumnValues(['bill_pobox']);
        $billStreet = $this->focusColumnValues(['bill_street']);
        $billCity	= $this->focusColumnValues(['bill_city']);
        $billState	= $this->focusColumnValues(['bill_state']);
        $billCountry = $this->focusColumnValues(['bill_country']);
        $billCode	=  $this->focusColumnValues(['bill_code']);
        $address	= $this->joinValues([$billPoBox, $billStreet], ' ');
        $address .= "\n" . $this->joinValues([$billCity, $billState], ',') . ' ' . $billCode;
        $address .= "\n" . $billCountry;

        return $address;
    }

    public function buildHeaderShippingAddress()
    {
        $shipPoBox	= $this->focusColumnValues(['ship_pobox']);
        $shipStreet = $this->focusColumnValues(['ship_street']);
        $shipCity	= $this->focusColumnValues(['ship_city']);
        $shipState	= $this->focusColumnValues(['ship_state']);
        $shipCountry = $this->focusColumnValues(['ship_country']);
        $shipCode	=  $this->focusColumnValues(['ship_code']);
        $address	= $this->joinValues([$shipPoBox, $shipStreet], ' ');
        $address .= "\n" . $this->joinValues([$shipCity, $shipState], ',') . ' ' . $shipCode;
        $address .= "\n" . $shipCountry;

        return $address;
    }

    public function buildCurrencySymbol()
    {
        global $adb;
        $currencyId = $this->focus->column_fields['currency_id'];
        if (!empty($currencyId)) {
            $result = $adb->pquery('SELECT currency_symbol FROM vtiger_currency_info WHERE id=?', [$currencyId]);

            return decode_html($adb->query_result($result, 0, 'currency_symbol'));
        }

        return false;
    }

    public function focusColumnValues($names, $delimeter = "\n")
    {
        if (!is_array($names)) {
            $names = [$names];
        }
        $values = [];
        foreach ($names as $name) {
            $value = $this->focusColumnValue($name, false);
            if ($value !== false) {
                $values[] = $value;
            }
        }

        return $this->joinValues($values, $delimeter);
    }

    public function focusColumnValue($key, $defvalue = '')
    {
        $focus = $this->focus;
        if (isset($focus->column_fields[$key])) {
            return decode_html($focus->column_fields[$key]);
        }

        return $defvalue;
    }

    public function resolveReferenceLabel($id, $module = false)
    {
        if (empty($id)) {
            return '';
        }
        if ($module === false) {
            $module = getSalesEntityType($id);
        }
        $label = getEntityName($module, [$id]);

        return decode_html($label[$id]);
    }

    public function joinValues($values, $delimeter = "\n")
    {
        $valueString = '';
        foreach ($values as $value) {
            if (empty($value)) {
                continue;
            }
            $valueString .= $value . $delimeter;
        }

        return rtrim($valueString, $delimeter);
    }

    public function formatNumber($value)
    {
        return number_format($value);
    }

    public function formatPrice($value, $decimal = 2)
    {
        $currencyField = new CurrencyField($value);

        return $currencyField->getDisplayValue(null, true);
    }

    public function formatDate($value)
    {
        return DateTimeField::convertToUserFormat($value);
    }
}
