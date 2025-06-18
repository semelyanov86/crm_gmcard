<?php

class VTEPayments_Ajax_Action extends Vtiger_IndexAjax_View
{
    public function checkPermission(Vtiger_Request $request) {}

    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('getInvoiceData');
        $this->exposeMethod('updateGrandTotal');
        $this->exposeMethod('updateTooltipArea');
        $this->exposeMethod('applyCredit');
        $this->exposeMethod('saveCredit');
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    public function getInvoiceData(Vtiger_Request $request)
    {
        $db = PearDatabase::getInstance();
        $crmid = trim($request->get('id'));
        if ($crmid > 0) {
            $sql = "SELECT a.accountid, a.accountname, ct.contactid, TRIM(CONCAT(ct.firstname,' ', ct.lastname)) AS contact_name\n                FROM vtiger_invoice i\n                INNER JOIN vtiger_crmentity c ON c.crmid=i.invoiceid\n                LEFT JOIN vtiger_account a ON a.accountid=i.accountid\n                LEFT JOIN vtiger_contactdetails ct ON ct.contactid=i.contactid\n                WHERE c.crmid=? AND c.deleted=0 LIMIT 1";
            $res = $db->pquery($sql, [$crmid]);
            if ($db->num_rows($res) > 0) {
                while ($row = $db->fetch_row($res)) {
                    $data['accountid'] = $row['accountid'];
                    $data['accountname'] = $row['accountname'];
                    $data['contactid'] = $row['contactid'];
                    $data['contact_name'] = $row['contact_name'];
                }
                $data = json_encode($data);
            }
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($data);
        $response->emit();
    }

    public function updateGrandTotal(Vtiger_Request $request)
    {
        $db = PearDatabase::getInstance();
        $result = [];
        $invoice_id = trim($request->get('invoice_id'));
        $sql = "SELECT * \n                    FROM vtiger_invoice i\n                    INNER JOIN vtiger_crmentity c ON c.crmid = i.invoiceid\n                    WHERE\n                    i.invoiceid = ? AND c.deleted= 0 ";
        $res = $db->pquery($sql, [$invoice_id]);
        $total_amount = 0;
        $total = 0;
        $balance = 0;
        if ($db->num_rows($res) > 0) {
            while ($row = $db->fetch_row($res)) {
                $total_amount = $row['received'];
                $total = $row['total'];
                $balance = $row['balance'];
            }
        }
        $total_amount = new CurrencyField($total_amount);
        $total = new CurrencyField($total);
        $balance = new CurrencyField($balance);
        $credit_available_amount = 0;
        $query = "SELECT p.* FROM vtiger_payments p\n            INNER JOIN vtiger_invoice i ON (i.accountid = p.organization AND i.accountid > 0)\n                OR (i.contactid = p.contact AND i.contactid > 0)\n            INNER JOIN vtiger_crmentity e ON e.crmid = p.paymentid\n            WHERE e.deleted = 0 AND i.invoiceid = ? AND p.payment_status = '*Credit'";
        $res = $db->pquery($query, [$invoice_id]);
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
        $result = ['total_amount' => $total_amount->getDisplayValue(), 'total' => $total->getDisplayValue(), 'balance' => $balance->getDisplayValue(), 'credit_available_amount' => $credit_available_amount->getDisplayValue()];
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($result);
        $response->emit();
    }

    public function updateTooltipArea(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $invoiceId = $request->get('invoice_id');
        $invModel = Vtiger_Record_Model::getInstanceById($invoiceId, 'Invoice');
        $split_amount = [25 / 100, 33 / 100, 50 / 100, 1];
        foreach ($split_amount as $_amount) {
            $_total_tooltip = new CurrencyField($_amount * $invModel->get('hdnGrandTotal'));
            $_balance_tooltip = new CurrencyField($_amount * $invModel->get('balance'));
            $amount_tooltip[$_amount * 100 . '%'] = ['total' => $_total_tooltip->getDisplayValue(), 'balance' => $_balance_tooltip->getDisplayValue()];
        }
        $currencyInfo = getInventoryCurrencyInfo('Invoice', $invoiceId);
        $currencySymbol = $currencyInfo['currency_symbol'];
        $viewer->assign('CURRENCY', $currencySymbol);
        $viewer->assign('AMOUNTS_TOOLTIP', $amount_tooltip);
        $viewer->assign('TOTAL_ORG', $invModel->get('hdnGrandTotal'));
        $viewer->assign('BALANCE_ORG', $invModel->get('balance'));
        echo $viewer->view('AmountTooltip.tpl', 'VTEPayments', true);
    }

    public function applyCredit(Vtiger_Request $request)
    {
        global $adb;
        $paymentid = $request->get('paymentid');
        $query = 'SELECT * FROM vtiger_payments WHERE paymentid = ?';
        $res = $adb->pquery($query, [$paymentid]);
        if ($adb->num_rows($res) > 0) {
            $payment_amount = CurrencyField::convertToUserFormat($adb->query_result($res, 0, 'amount_paid'));
            $accountid = $adb->query_result($res, 0, 'organization');
            $contactid = $adb->query_result($res, 0, 'contact');
        }
        $list_payments = [];
        if ($accountid > 0 || $contactid > 0) {
            $query = "SELECT p.*, i.invoice_no FROM vtiger_payments p\n                INNER JOIN vtiger_crmentity e ON e.crmid = p.paymentid AND e.deleted = 0\n                LEFT JOIN vtiger_invoice i ON i.invoiceid = p.invoice\n                WHERE p.payment_status = '*Credit' AND p.amount_paid > 0";
            if ($accountid > 0 && $contactid > 0) {
                $query .= ' AND (organization = ' . $accountid . ' OR contact = ' . $contactid . ')';
            } else {
                if ($accountid > 0) {
                    $query .= ' AND (organization = ' . $accountid . ')';
                } else {
                    if ($contactid > 0) {
                        $query .= ' AND (organization = ' . $contactid . ')';
                    }
                }
            }
            $res = $adb->pquery($query, []);

            while ($row = $adb->fetchByAssoc($res)) {
                $queryCredit = "SELECT SUM(c.credit_amount) credit_amount\n                    FROM vtiger_credits c \n                        INNER JOIN vtiger_crmentity e ON e.crmid = c.creditid\n                    WHERE e.deleted = 0 AND c.vtepaymentid = ?";
                $resCredit = $adb->pquery($queryCredit, [$row['paymentid']]);
                if ($adb->num_rows($resCredit) > 0) {
                    $row['amount_paid'] -= floatval($adb->query_result($resCredit, 0, 'credit_amount'));
                }
                $row['amount_paid'] = CurrencyField::convertToUserFormat($row['amount_paid']);
                $list_payments[] = $row;
            }
        }
        $viewer = $this->getViewer($request);
        $viewer->assign('PAYMENTID', $paymentid);
        $viewer->assign('PAYMENT_AMOUNT', $payment_amount);
        $viewer->assign('LIST_PAYMENTS', $list_payments);
        $viewer->assign('ZERO_VALUE', CurrencyField::convertToUserFormat(0));
        echo $viewer->view('ApplyCredit.tpl', 'VTEPayments', true);
    }

    public function saveCredit(Vtiger_Request $request)
    {
        global $adb;
        $current_paymentid = $request->get('current_paymentid');
        $credit_paymentid = $request->get('credit_paymentid');
        $credit_amount_applied = $request->get('credit_amount_applied');
        $new_amount = $request->get('new_amount');
        $remaining_credit = $request->get('remaining_credit');
        $query = 'SELECT * FROM vtiger_payments WHERE paymentid = ?';
        $res = $adb->pquery($query, [$current_paymentid]);
        if ($adb->num_rows($res) > 0) {
            $invoiceid2 = $adb->query_result($res, 0, 'invoice');
        }
        $query = 'SELECT * FROM vtiger_payments WHERE paymentid = ?';
        $res = $adb->pquery($query, [$credit_paymentid]);
        if ($adb->num_rows($res) > 0) {
            $accountid = $adb->query_result($res, 0, 'organization');
            $contactid = $adb->query_result($res, 0, 'contact');
            $invoiceid = $adb->query_result($res, 0, 'invoice');
        }
        $obj_credit = CRMEntity::getInstance('VTEPaymentCredits');
        $obj_credit->column_fields['invoiceid'] = $invoiceid;
        $obj_credit->column_fields['accountid'] = $accountid;
        $obj_credit->column_fields['contactid'] = $contactid;
        $obj_credit->column_fields['vtepaymentid'] = $credit_paymentid;
        $obj_credit->column_fields['invoiceid2'] = $invoiceid2;
        $obj_credit->column_fields['vtepaymentid2'] = $current_paymentid;
        $obj_credit->column_fields['credit_amount'] = $credit_amount_applied;
        $obj_credit->column_fields['credit_status'] = 'Credited';
        $obj_credit->save('VTEPaymentCredits');
        if (intval($remaining_credit) == 0) {
            $obj_payment = Vtiger_Record_Model::getInstanceById($credit_paymentid, 'VTEPayments');
            $obj_payment->set('mode', 'edit');
            $obj_payment->set('payment_status', '*Credit - Used');
            $obj_payment->set('amount_paid', 0);
            $obj_payment->save();
        }
        if (floatval($remaining_credit) > 0) {
            $obj_payment = Vtiger_Record_Model::getInstanceById($credit_paymentid, 'VTEPayments');
            $obj_payment->set('mode', 'edit');
            $obj_payment->set('payment_status', '*Credit');
            $obj_payment->set('amount_paid', $remaining_credit);
            $obj_payment->save();
        }
        if (intval($new_amount) == 0) {
            $obj_payment = Vtiger_Record_Model::getInstanceById($current_paymentid, 'VTEPayments');
            $obj_payment->set('mode', 'edit');
            $obj_payment->set('payment_status', 'Credit Applied');
            $obj_payment->save();
        }
        if (floatval($new_amount) > 0) {
            $obj_payment = Vtiger_Record_Model::getInstanceById($current_paymentid, 'VTEPayments');
            $obj_payment->set('mode', 'edit');
            $obj_payment->set('payment_status', 'Credit Applied');
            $obj_payment->set('amount_paid', $credit_amount_applied);
            $obj_payment->save();
            $obj_payment = CRMEntity::getInstance('VTEPayments');
            $obj_payment->retrieve_entity_info($current_paymentid, 'VTEPayments');
            $obj_payment->column_fields['payment_status'] = '*Unpaid';
            $obj_payment->column_fields['amount_paid'] = $new_amount;
            $obj_payment->mode = '';
            $obj_payment->save('VTEPayments');
        }
    }
}
