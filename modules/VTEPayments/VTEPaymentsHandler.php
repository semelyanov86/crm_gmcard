<?php

class VTEPaymentsHandler extends VTEventHandler
{
    public function handleEvent($eventName, $entityData)
    {
        global $log;
        global $adb;
        if ($eventName == 'vtiger.entity.aftersave') {
            $moduleName = $entityData->getModuleName();
            if ($moduleName == 'VTEPayments') {
                $objId = $entityData->getId();
                $sql = 'SELECT invoice FROM vtiger_payments WHERE paymentid=?';
                $results = $adb->pquery($sql, [$objId]);
                if ($adb->num_rows($results) > 0) {
                    $invoiceId = $adb->query_result($results, 0, 'invoice');
                    if (!empty($invoiceId)) {
                        $this->recalculateInvoiceTotal($invoiceId);
                        if ($entityData->isNew()) {
                            $organization_id = $entityData->get('organization');
                            if (empty($organization_id)) {
                                $adb->pquery("UPDATE vtiger_payments p \n                                                    INNER JOIN vtiger_invoice i ON i.`invoiceid`=p.`invoice` \n                                                    SET p.`organization`=i.`accountid`\n                                                    WHERE p.paymentid = ?", [$objId]);
                            }
                        }
                    }
                }
            }
        }
        if ($eventName == 'vtiger.entity.aftersave.final') {
            $moduleName = $entityData->getModuleName();
            if ($moduleName == 'Invoice') {
                $isNew = $entityData->isNew();
                if (!$isNew) {
                    $invoiceId = $entityData->getId();
                    $invoice_status = $entityData->get('invoicestatus');
                    if ($invoice_status != 'Paid') {
                        $sql = "SELECT\n\t\t\t\t\t\t\t\t\tvtiger_payments.*, vtiger_invoice.total\n\t\t\t\t\t\t\t\tFROM\n\t\t\t\t\t\t\t\t\tvtiger_payments\n\t\t\t\t\t\t\t\tINNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_payments.paymentid\n\t\t\t\t\t\t\t\tLEFT JOIN vtiger_invoice ON vtiger_invoice.invoiceid = vtiger_payments.invoice\n\t\t\t\t\t\t\t\tWHERE\n\t\t\t\t\t\t\t\t\tvtiger_payments.invoice = ?\n\t\t\t\t\t\t\t\tAND vtiger_crmentity.deleted = 0";
                        $results = $adb->pquery($sql, [$invoiceId]);
                        $num_payments = $adb->num_rows($results);
                        if ($num_payments == 1) {
                            $reference = $adb->query_result($results, 0, 'reference');
                            $amount = $adb->query_result($results, 0, 'amount_paid');
                            $payment_status = $adb->query_result($results, 0, 'payment_status');
                            $grand_total = $adb->query_result($results, 0, 'total');
                            if ((float) $amount != (float) $grand_total && $payment_status != 'Paid' && $payment_status != 'Completed' && $reference != 'Web Payment' && !(isset($_REQUEST['recalculateInvoiceTotal']) && $_REQUEST['recalculateInvoiceTotal'] === true)) {
                                $vtePaymentId = $adb->query_result($results, 0, 'paymentid');
                                $adb->pquery('UPDATE vtiger_payments SET amount_paid = ? WHERE paymentid = ?', [$grand_total, $vtePaymentId]);
                            }
                        }
                    }
                }
            }
        }
        if ($eventName == 'vtiger.entity.afterdelete') {
            $moduleName = $entityData->getModuleName();
            if ($moduleName == 'Invoice') {
                $invoiceId = $entityData->getId();
                $sql = "UPDATE vtiger_crmentity\n\t\t\t\t\t\tINNER JOIN vtiger_payments ON vtiger_payments.paymentid = vtiger_crmentity.crmid\n\t\t\t\t\t\tSET vtiger_crmentity.deleted = 1\n\t\t\t\t\t\tWHERE\n\t\t\t\t\t\t\tvtiger_payments.invoice = ?\n\t\t\t\t\t\tAND vtiger_crmentity.deleted = 0";
                $adb->pquery($sql, [$invoiceId]);
            }
            if ($moduleName == 'VTEPayments') {
                $objId = $entityData->getId();
                $sql = 'SELECT invoice FROM vtiger_payments WHERE paymentid=?';
                $results = $adb->pquery($sql, [$objId]);
                if ($adb->num_rows($results) > 0) {
                    $invoiceId = $adb->query_result($results, 0, 'invoice');
                    if (!empty($invoiceId)) {
                        $this->recalculateInvoiceTotal($invoiceId);
                    }
                }
            }
        }
    }

    public function recalculateInvoiceTotal($invoiceId)
    {
        global $adb;
        $sql = "SELECT SUM(p.amount_paid) as total_amount,i.total as total\n                                FROM vtiger_payments p\n                                INNER JOIN vtiger_crmentity c ON c.crmid = p.paymentid\n                                LEFT JOIN vtiger_invoice i ON i.invoiceid = p.invoice\n                                WHERE p.invoice = ? AND c.deleted= 0 AND p.payment_status IN('Paid', 'Completed', 'Credit Applied', 'Deduction', '*Refund', '*Returned')";
        $res = $adb->pquery($sql, [$invoiceId]);
        $total_received = 0;
        if ($adb->num_rows($res) > 0) {
            while ($row = $adb->fetch_row($res)) {
                $total_received = $row['total_amount'];
            }
        }
        $invoiceRecordModel = Vtiger_Record_Model::getInstanceById($invoiceId, 'Invoice');
        $dataInvoice = $invoiceRecordModel->getData();
        $total = $invoiceRecordModel->get('hdnGrandTotal');
        $balance = $total - $total_received;
        $invoiceRecordModel->set('mode', 'edit');
        $invoiceRecordModel->set('balance', CurrencyField::convertToUserFormat($balance));
        $invoiceRecordModel->set('received', CurrencyField::convertToUserFormat($total_received));
        if ($balance == 0) {
            $invoiceRecordModel->set('invoicestatus', 'Paid');
        } else {
            $invoiceRecordModel->set('invoicestatus', 'Partially Paid');
        }
        $invoiceRecordModel->isLineItemUpdate = false;
        $_REQUEST['action'] = 'InvoiceAjax';
        $_REQUEST['recalculateInvoiceTotal'] = true;
        $invoiceRecordModel->save();
        unset($invoiceRecordModel, $_REQUEST['action'], $_REQUEST['recalculateInvoiceTotal']);


    }
}
