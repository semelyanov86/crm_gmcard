<?php
/* * *******************************************************************************
 * The content of this file is subject to the EMAIL Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class EMAILMaker_SaveEMAILTemplate_Action extends Vtiger_Action_Controller
{

    public function checkPermission(Vtiger_Request $request)
    {
    }

    public function process(Vtiger_Request $request)
    {
        EMAILMaker_Debugger_Model::GetInstance()->Init();
        $adb = PearDatabase::getInstance();

        $adb->println("TRANS save emailmaker starts");
        $adb->startTransaction();

        $cu_model = Users_Record_Model::getCurrentUserModel();
        $EMAILMaker = new EMAILMaker_EMAILMaker_Model();
        $S_Data = $request->getAll();
        $templatename = $request->get('templatename');
        $modulename = $request->get('modulename');
        $templateid = $request->get('templateid');
        $description = $request->get('description');
        $subject = $request->get('subject');
        $is_theme = $request->get('is_theme');
        $body = $S_Data['body'];
        $owner = $request->get('template_owner');
        $sharingtype = $request->get('sharing');
        $email_category = $request->get('email_category');
        $is_active = $request->get('is_active');
        $is_default_dv = $request->get('is_default_dv');
        $is_default_lv = $request->get('is_default_lv');
        $is_listview = $request->get('is_listview');
        if ($is_default_dv != "") {
            $is_default_dv = "1";
        } else {
            $is_default_dv = "0";
        }
        if ($is_default_lv != "") {
            $is_default_lv = "1";
        } else {
            $is_default_lv = "0";
        }
        if ($is_listview != "") {
            $is_listview = "1";
        } else {
            $is_listview = "0";
        }
        $order = $request->get('tmpl_order');

        if (isset($templateid) && $templateid != '') {
            $params1 = array($templatename, $modulename, $description, $subject, $body, $owner, $sharingtype, $email_category, $is_listview, $templateid);
            $adb->pquery("update vtiger_emakertemplates set templatename =?, module =?, description =?, subject =?, body =?, owner=?, sharingtype = ?, category = ?, is_listview = ? where templateid =?", $params1);
            $adb->pquery("DELETE FROM vtiger_emakertemplates_userstatus WHERE templateid=? AND userid=?", array($templateid, $cu_model->id));
            $adb->pquery("DELETE FROM vtiger_emakertemplates_default_from WHERE templateid=? AND userid=?", array($templateid, $cu_model->id));
        } else {
            $templateid = $adb->getUniqueID('vtiger_emakertemplates');
            $sql3 = "insert into vtiger_emakertemplates (templatename,module,description,subject,body,deleted,is_listview,is_theme,templateid,owner,sharingtype, category) values (?,?,?,?,?,?,?,?,?,?,?,?)";
            $params3 = array($templatename, $modulename, $description, $subject, $body, 0, $is_listview, $is_theme, $templateid, $owner, $sharingtype, $email_category);
            $adb->pquery($sql3, $params3);
        }
        $dec_point = $request->get('dec_point');
        $dec_decimals = $request->get('dec_decimals');
        $dec_thousands = $request->get('dec_thousands');
        if ($dec_thousands == " ") {
            $dec_thousands = "sp";
        }
        $sql4A = "SELECT * FROM vtiger_emakertemplates_settings";
        $result4A = $adb->pquery($sql4A, array());
        $num_rows4A = $adb->num_rows($result4A);
        if ($num_rows4A > 0) {
            $sql4B = "UPDATE vtiger_emakertemplates_settings SET decimals = ?, decimal_point = ?, thousands_separator = ?";
        } else {
            $sql4B = "INSERT INTO vtiger_emakertemplates_settings (decimals, decimal_point, thousands_separator) VALUES (?,?,?)";
        }
        $params4B = array($dec_decimals, $dec_point, $dec_thousands);
        $adb->pquery($sql4B, $params4B);
//ignored picklist values
        $adb->pquery("DELETE FROM vtiger_emakertemplates_ignorepicklistvalues", array());
        $ignore_picklist_values = $request->get('ignore_picklist_values');
        $pvvalues = explode(",", $ignore_picklist_values);
        foreach ($pvvalues as $value) {
            $adb->pquery("INSERT INTO vtiger_emakertemplates_ignorepicklistvalues(value) VALUES(?)", array(trim($value)));
        }
// end ignored picklist values
//unset the former default template because only one template can be default per user x module
        $is_default_bin = $is_default_lv . $is_default_dv;
        $is_default_dec = intval(base_convert($is_default_bin, 2, 10)); // convert binary format xy to decimal; where x stands for is_default_lv and y stands for is_default_dv
        if ($is_default_dec > 0) {
            $sql5 = "UPDATE vtiger_emakertemplates_userstatus
            INNER JOIN vtiger_emakertemplates USING(templateid)
            SET is_default=?
            WHERE is_default=? AND userid=? AND module=?";
            switch ($is_default_dec) {
//      in case of only is_default_dv is checked
                case 1:
                    $adb->pquery($sql5, array("0", "1", $cu_model->id, $modulename));
                    $adb->pquery($sql5, array("2", "3", $cu_model->id, $modulename));
                    break;
//      in case of only is_default_lv is checked
                case 2:
                    $adb->pquery($sql5, array("0", "2", $cu_model->id, $modulename));
                    $adb->pquery($sql5, array("1", "3", $cu_model->id, $modulename));
                    break;
//      in case of both is_default_* are checked
                case 3:
                    $sql5 = "UPDATE vtiger_emakertemplates_userstatus
                    INNER JOIN vtiger_emakertemplates USING(templateid)
                    SET is_default=?
                    WHERE is_default > ? AND userid=? AND module=?";
                    $adb->pquery($sql5, array("0", "0", $cu_model->id, $modulename));
            }
        }
        $adb->pquery("INSERT INTO vtiger_emakertemplates_userstatus(templateid, userid, is_active, is_default, sequence) VALUES(?,?,?,?,?)", array($templateid, $cu_model->id, $is_active, $is_default_dec, $order));
//SHARING
        $adb->pquery("DELETE FROM vtiger_emakertemplates_sharing WHERE templateid=?", array($templateid));

        $member_array = $request->get('members');
        if ($sharingtype == "share" && count($member_array) > 0) {
            $groupMemberArray = self::constructSharingMemberArray($member_array);

            $sql8a = "INSERT INTO vtiger_emakertemplates_sharing(templateid, shareid, setype) VALUES ";
            $sql8b = "";
            $params8 = array();
            foreach ($groupMemberArray as $setype => $shareIdArr) {
                foreach ($shareIdArr as $shareId) {
                    $sql8b .= "(?, ?, ?),";
                    $params8[] = $templateid;
                    $params8[] = $shareId;
                    $params8[] = $setype;
                }
            }

            if ($sql8b != "") {
                $sql8b = rtrim($sql8b, ",");
                $sql8 = $sql8a . $sql8b;
                $adb->pquery($sql8, $params8);
            }
        }
        //DEFAULT FROM SETTING
        $default_from_email = $request->get('default_from_email');
        if ($default_from_email != "") {
            $adb->pquery("INSERT INTO vtiger_emakertemplates_default_from (templateid,userid,fieldname) VALUES (?,?,?)", array($templateid, $cu_model->id, $default_from_email));
        }

        $adb->pquery("DELETE FROM vtiger_emakertemplates_displayed WHERE templateid=?", array($templateid));

        $displayed_value = $request->get('displayedValue');
        $display_conditions = Zend_Json::encode($request->get('display_conditions'));
        $adb->pquery("INSERT INTO vtiger_emakertemplates_displayed (templateid,displayed,conditions) VALUES (?,?,?)", array($templateid, $displayed_value, $display_conditions));

        $EMAILMaker->AddLinks($modulename);

        $adb->completeTransaction();
        $adb->println("TRANS save emailmaker ends");

        $redirect = $request->get('redirect');
        if ($redirect == "false") {
            $redirect_url = "index.php?module=EMAILMaker&view=Edit&applied=true&record=" . $templateid;
            $return_module = $request->get('return_module');
            $return_view = $request->get('return_view');
            if ($return_module != "") {
                $redirect_url .= "&return_module=" . $return_module;
            }
            if ($return_view != "") {
                $redirect_url .= "&return_view=" . $return_view;
            }
            header("Location:" . $redirect_url);
        } else {
            if ($is_theme == "1") {
                header("Location:index.php?module=EMAILMaker&view=Edit&mode=selectTheme&return_module=EMAILMaker&return_view=List");
            } else {
                header("Location:index.php?module=EMAILMaker&view=Detail&record=" . $templateid);
            }
        }
    }

    private function constructSharingMemberArray($member_array)
    {

        $groupMemberArray = $roleArray = $roleSubordinateArray = $groupArray = $userArray = array();

        foreach ($member_array as $member) {
            $memSubArray = explode(':', $member);
            switch ($memSubArray[0]) {
                case "Groups":
                    $groupArray[] = $memSubArray[1];
                    break;
                case "Roles":
                    $roleArray[] = $memSubArray[1];
                    break;
                case "RoleAndSubordinates":
                    $roleSubordinateArray[] = $memSubArray[1];
                    break;
                case "Users":
                    $userArray[] = $memSubArray[1];
                    break;
            }
        }

        $groupMemberArray['groups'] = $groupArray;
        $groupMemberArray['roles'] = $roleArray;
        $groupMemberArray['rs'] = $roleSubordinateArray;
        $groupMemberArray['users'] = $userArray;

        return $groupMemberArray;
    }
}