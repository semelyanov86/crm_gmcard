<?php

class VTEPayments_ManagePayments_View extends Vtiger_IndexAjax_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function process(Vtiger_Request $request)
    {
        $current_user = Users_Record_Model::getCurrentUserModel();
        $db = PearDatabase::getInstance();
        $viewer = $this->getViewer($request);
        $invoiceId = $request->get('invoiceid');
        $moduleName = $request->get('module');
        $record = $request->get('record');
        if (!empty($record)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleName);
            $viewer->assign('RECORD_ID', $record);
            $viewer->assign('MODE', 'edit');
            $invModel = Vtiger_Record_Model::getInstanceById($invoiceId, 'Invoice');
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $invModel = Vtiger_Record_Model::getInstanceById($invoiceId, 'Invoice');
            $recordModel->set('organization', $invModel->get('account_id'));
            $recordModel->set('contact', $invModel->get('contact_id'));
            $recordModel->set('invoice', $invoiceId);
            $balance = new CurrencyField(floatval($invModel->get('balance')));
            $total = new CurrencyField($invModel->get('hdnGrandTotal'));
            $split_amount = [25 / 100, 33 / 100, 50 / 100, 1];
            foreach ($split_amount as $_amount) {
                $_total_tooltip = new CurrencyField($_amount * $invModel->get('hdnGrandTotal'));
                $_balance_tooltip = new CurrencyField($_amount * $invModel->get('balance'));
                $amount_tooltip[$_amount * 100 . '%'] = ['total' => $_total_tooltip->getDisplayValue(null, true), 'balance' => $_balance_tooltip->getDisplayValue(null, true)];
            }
            $received = new CurrencyField(floatval($invModel->get('received')));
            $currencyInfo = getInventoryCurrencyInfo('Invoice', $invoiceId);
            $currencySymbol = $currencyInfo['currency_symbol'];
            $viewer->assign('CURRENCY', $currencySymbol);
            $viewer->assign('AMOUNTS_TOOLTIP', $amount_tooltip);
            $viewer->assign('BALANCE', $balance->getDisplayValue(null, true));
            $viewer->assign('TOTAL', $total->getDisplayValue(null, true));
            $viewer->assign('TOTAL_ORG', $invModel->get('hdnGrandTotal'));
            $viewer->assign('BALANCE_ORG', $invModel->get('balance'));
            $viewer->assign('RECEIVED', $received->getDisplayValue(null, true));
            $viewer->assign('MODE', '');
        }
        $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
        $an_integrate_status = false;
        if (file_exists('modules/ANCustomers/libs/AuthnetHelper.php')) {
            require_once 'modules/ANCustomers/libs/AuthnetHelper.php';
            $authnetHelper = new AuthnetHelper();
            $an_integrate_status = $authnetHelper->isANEnable();
        }
        $viewer->assign('AN_INTEGRATE_STATUS', $an_integrate_status);
        $sql = "SELECT * FROM vtiger_payments p\r\n            INNER JOIN vtiger_paymentscf pc ON pc.paymentid = p.paymentid\r\n            INNER JOIN vtiger_crmentity c ON c.crmid = p.paymentid\r\n            WHERE c.deleted = 0 AND p.invoice = ? ORDER BY p.date DESC, c.createdtime DESC";
        $res = $db->pquery($sql, [$invoiceId]);
        $credit_available_amount = 0;

        while ($row = $db->fetch_row($res)) {
            if (empty($row['date'])) {
            }
            if ($row['date']) {
                $row['date'] = DateTimeField::convertToUserFormat($row['date']);
            }
            if ($row['amount_paid']) {
                $amount_paid = new CurrencyField($row['amount_paid']);
            }
            if ($row['description']) {
                $row['description'] = nl2br($row['description']);
            }
            if ($an_integrate_status) {
                $query = "SELECT * FROM vtiger_antransactions\r\n                            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_antransactions.antransactionsid\r\n                            WHERE vtiger_crmentity.deleted = 0 AND vtiger_antransactions.payment_id = ?\r\n                            ORDER BY vtiger_crmentity.createdtime DESC";
                $result = $db->pquery($query, [$row['paymentid']]);
                $transactions = [];
                if ($db->num_rows($result)) {
                    while ($row1 = $db->fetchByAssoc($result)) {
                        $createdTimeObj = new DateTimeField($row1['createdtime']);
                        $row1['an_date'] = $createdTimeObj->getDisplayDateTimeValue($current_user);
                        $amount_display = new CurrencyField($row1['amount']);
                        $row1['amount_display'] = $amount_display->getDisplayValue(null, true);
                        if ($row1['an_description']) {
                            $row1['an_description'] = decode_html($row1['an_description']);
                            $row1['an_description'] = nl2br($row1['an_description']);
                        }
                        $transactions[] = $row1;
                    }
                }
                $row['an_transactions'] = $transactions;
                $row['an_transactions_count'] = count($transactions);
                $row['an_transactions_success'] = 0;
                if (!empty($transactions)) {
                    foreach ($transactions as $transaction) {
                        if ($transaction['an_status'] != 'Error') {
                            $row['an_transactions_success'] = 1;
                        }
                    }
                }
            }
            $totalCreditAmout = 0;
            $modulePaymentCredits = Vtiger_Module::getInstance('VTEPaymentCredits');
            if (!empty($modulePaymentCredits) && ($row['payment_status'] == '*Credit' || $row['payment_status'] == 'Credit Applied' || $row['payment_status'] == '*Credit - Used' || $row['payment_status'] == '*Credit Used' || $row['payment_status'] == 'Paid')) {
                $query_credits = "SELECT e.createdtime, i1.subject subject1, p1.amount_paid amount_paid1,\r\n                    c.credit_amount, i2.subject subject2, p2.amount_paid amount_paid2,\r\n                    c.creditid, i1.invoiceid invoiceid1, p1.paymentid paymentid1, i2.invoiceid invoiceid2, p2.paymentid paymentid2\r\n                    FROM vtiger_credits c\r\n                    INNER JOIN vtiger_crmentity e ON e.crmid = c.creditid\r\n                    LEFT JOIN vtiger_invoice i1 ON i1.invoiceid = c.invoiceid\r\n                    LEFT JOIN vtiger_invoice i2 ON i2.invoiceid = c.invoiceid2\r\n                    LEFT JOIN vtiger_payments p1 ON p1.paymentid = c.vtepaymentid\r\n                    LEFT JOIN vtiger_payments p2 ON p2.paymentid = c.vtepaymentid2\r\n                    WHERE e.deleted = 0 AND (c.vtepaymentid = ? OR c.vtepaymentid2 = ?)";
                $res_credits = $db->pquery($query_credits, [$row['paymentid'], $row['paymentid']]);
                $list_credits = [];

                while ($row_credits = $db->fetchByAssoc($res_credits)) {
                    $row_credits['amount_paid1'] = CurrencyField::convertToUserFormat($row_credits['amount_paid1']);
                    $row_credits['credit_amount'] = CurrencyField::convertToUserFormat($row_credits['credit_amount']);
                    $row_credits['amount_paid2'] = CurrencyField::convertToUserFormat($row_credits['amount_paid2']);
                    $list_credits[] = $row_credits;
                    if ($row['paymentid'] == $row_credits['paymentid1']) {
                        $totalCreditAmout += $row_credits['credit_amount'];
                    }
                }
                $row['credits'] = $list_credits;
            }
            $row['amount_paid'] -= $totalCreditAmout;
            $row['amount_paid'] = CurrencyField::convertToUserFormat($row['amount_paid']);
            $payments[] = $row;
        }
        $viewer->assign('PAYMENTS', $payments);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
        $viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
        $viewer->assign('USER_MODEL', $current_user);
        $viewer->assign('VIEWNAME', $request->get('view'));
        $credit_available_amount = 0;
        $query = "SELECT p.* FROM vtiger_payments p\r\n            INNER JOIN vtiger_invoice i ON (i.accountid = p.organization AND i.accountid > 0)\r\n                OR (i.contactid = p.contact AND i.contactid > 0)\r\n            INNER JOIN vtiger_crmentity e ON e.crmid = p.paymentid\r\n            WHERE e.deleted = 0 AND i.invoiceid = ? AND p.payment_status = '*Credit'";
        $res = $db->pquery($query, [$invoiceId]);
        if ($db->num_rows($res) > 0) {
            while ($row = $db->fetch_row($res)) {
                $queryCredit = 'SELECT SUM(credit_amount) credit_amount FROM vtiger_credits WHERE vtepaymentid = ?';
                $resCredit = $db->pquery($queryCredit, [$row['paymentid']]);
                $creditAmount = 0;
                if ($db->num_rows($resCredit) > 0) {
                    $creditAmount = $db->query_result($resCredit, 0, 'credit_amount');
                }
                $credit_available_amount += $row['amount_paid'] - $creditAmount;
            }
        }
        $credit_available_amount = new CurrencyField(floatval($credit_available_amount));
        $credit_available_amount = $credit_available_amount->getDisplayValue(null, true);
        $viewer->assign('CREDIT_AVAILABLE_AMOUNT', $credit_available_amount);
        echo $viewer->view('ManagePayments.tpl', 'VTEPayments', true);
    }
}
